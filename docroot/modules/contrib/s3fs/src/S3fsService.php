<?php

namespace Drupal\s3fs;

use ArrayIterator;
use Aws\S3\Exception\S3Exception;
use Aws\S3\S3Client;
use Drupal\Core\Cache\Cache;
use Drupal\Core\Config\ConfigFactory;
use Drupal\Core\Database\Connection;
use Drupal\Core\Database\SchemaObjectExistsException;
use Drupal\Core\StreamWrapper\PrivateStream;
use Drupal\Core\StreamWrapper\PublicStream;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\s3fs\StreamWrapper\S3fsStream;

/**
 * Defines a S3fsService service.
 */
class S3fsService implements S3fsServiceInterface {

  use StringTranslationTrait;

  /**
   * The database connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $connection;

  /**
   * The config factory object.
   *
   * @var \Drupal\Core\Config\ConfigFactory
   */
  protected $configFactory;

  /**
   * Constructs an S3fsService object.
   *
   * @param \Drupal\Core\Database\Connection $connection
   *   The new database connection object.
   * @param \Drupal\Core\Config\ConfigFactory $config_factory
   *   The config factory object.
   */
  public function __construct(Connection $connection, ConfigFactory $config_factory) {
    $this->connection = $connection;
    $this->configFactory = $config_factory;
  }

  /**
   * {@inheritdoc}
   *
   * Validate the S3fs config.
   *
   * @param array $config
   *   Array of configuration settings from which to configure the client.
   * @param bool $returnError
   *   Boolean, False by default.
   *
   * @return bool|array
   *   Returns a boolean value or an array.
   */
  public function validate(array $config, $returnError = FALSE) {
    if (!class_exists('Aws\S3\S3Client')) {
      if ($returnError) {
        return [
          'form',
          $this->t('Cannot load Aws\S3\S3Client class. Please ensure that the aws sdk php library is installed correctly.'),
        ];
      }
      return FALSE;
    }
    elseif (!$config['use_instance_profile'] && (!$config['secret_key'] || !$config['access_key'])) {
      if ($returnError) {
        return [
          'form',
          $this->t("Your AWS credentials have not been properly configured.
          Please set them on the S3 File System admin/config/media/s3fs page or
          set \$config['s3fs.settings']['access_key'] and \$config['s3fs.settings']['secret_key'] in settings.php."),
        ];
      }
      return FALSE;
    }
    elseif ($config['use_instance_profile'] && empty($config['default_cache_config'])) {
      if ($returnError) {
        return [
          'default_cache_config',
          $this->t("Your AWS credentials have not been properly configured.
          You are attempting to use instance profile credentials but you have not set a default cache location.
          Please set it on the admin/config/media/s3fs page or set \$config['s3fs.settings']['cache_config'] in settings.php."),
        ];
      }
      return FALSE;
    }

    if (empty($config['bucket'])) {
      if ($returnError) {
        return [
          'bucket',
          $this->t('Your AmazonS3 bucket name is not configured.'),
        ];
      }
      return FALSE;
    }

    if (!empty($config['use_customhost']) && empty($config['hostname'])) {
      if ($returnError) {
        $name = 'hostname';
        $msg = $this->t('You must specify a Hostname to use the Custom Host feature.');
        return [$name, $msg];
      }
      return FALSE;
    }
    if (!empty($config['use_cname']) && empty($config['domain'])) {
      if ($returnError) {
        $name = 'domain';
        $msg = $this->t('You must specify a CDN Domain Name to use the CNAME feature.');
        return [$name, $msg];
      }
      return FALSE;
    }

    try {
      $s3 = $this->getAmazonS3Client($config);
    }
    catch (S3Exception $e) {
      if ($returnError) {
        $name = 'form';
        $msg = $e->getMessage();
        return [$name, $msg];
      }
      return FALSE;
    }

    // Test the connection to S3, and the bucket name.
    try {
      // listObjects() will trigger descriptive exceptions if the credentials,
      // bucket name, or region are invalid/mismatched.
      $s3->listObjects(['Bucket' => $config['bucket'], 'MaxKeys' => 1]);
    }
    catch (S3Exception $e) {
      if ($returnError) {
        $name = 'form';
        $msg = $this->t('An unexpected error occurred. @message', ['@message' => $e->getMessage()]);
        return [$name, $msg];
      }
      return FALSE;
    }

    return TRUE;
  }

  /**
   * {@inheritdoc}
   *
   * Sets up the S3Client object.
   * For performance reasons, only one S3Client object will ever be created
   * within a single request.
   *
   * @param array $config
   *   Array of configuration settings from which to configure the client.
   *
   * @return \Aws\S3\S3Client
   *   The fully-configured S3Client object.
   *
   * @throws \Drupal\s3fs\S3fsException
   *   The S3fs Exception.
   */
  public function getAmazonS3Client(array $config) {
    $s3 = &drupal_static(__METHOD__ . '_S3Client');
    $static_config = &drupal_static(__METHOD__ . '_static_config');

    // If the client hasn't been set up yet, or the config given to this call is
    // different from the previous call, (re)build the client.
    if (!isset($s3) || $static_config != $config) {

      // Create the Aws\S3\S3Client object.
      if (!empty($config['use_instance_profile'])) {
        $client_config = ['default_cache_config' => $config['default_cache_config']];
      }
      else {
        $client_config['credentials'] = [
          'key' => $config['access_key'],
          'secret' => $config['secret_key'],
        ];
      }
      if (!empty($config['region'])) {
        $client_config['region'] = $config['region'];
        // Signature v4 is only required in the Beijing and Frankfurt regions.
        // Also, setting it will throw an exception if a region hasn't been set.
        $client_config['signature'] = 'v4';
      }
      if (!empty($config['use_customhost']) && !empty($config['hostname'])) {
        $client_config['endpoint'] = ($config['use_https'] ? 'https://' : 'http://') . $config['hostname'];
      }
      $client_config['version'] = S3fsStream::API_VERSION;
      $s3 = new S3Client($client_config);
      $static_config = $config;
    }
    return $s3;
  }

  /**
   * {@inheritdoc}
   *
   * Copies all the local files from the specified file system into S3.
   *
   * @param array $config
   *   An s3fs configuration array.
   * @param string $scheme
   *   A variable defining which scheme (Public or Private) to copy.
   *
   * @todo implements a Batch, UI and Drush compatible
   */
  public function copyFileSystemToS3(array $config, $scheme) {
    if ($scheme == 'public') {
      $source_folder = realpath(PublicStream::basePath());
      $target_folder = !empty($config['public_folder']) ? $config['public_folder'] . '/' : 's3fs-public/';
    }
    elseif ($scheme == 'private') {
      $source_folder = PrivateStream::basePath() ? PrivateStream::basePath() : '';
      $source_folder_real = realpath($source_folder);
      if (empty($source_folder) || empty($source_folder_real)) {
        drupal_set_message($this->t('Private file system base path is unknown. Unable to perform S3 copy.'), 'error');
        return;
      }
      $target_folder = !empty($config['private_folder']) ? $config['private_folder'] . '/' : 's3fs-private/';
    }
    else {
      drupal_set_message($this->t('Scheme @scheme is not allowed', ['%scheme' => $scheme]), 'error');
      return;
    }

    if (!empty($config['root_folder'])) {
      $target_folder = "{$config['root_folder']}/$target_folder";
    }

    $file_paths = $this->dirScan($source_folder);
    foreach ($file_paths as $path) {
      $relative_path = $target_folder . str_replace($source_folder . '/', '', $path);

      // Print messages only for CLI requests.
      if (PHP_SAPI === 'cli') {
        print "Copying $scheme://$relative_path into S3...\n";
      }

      // Finally get to make use of S3fsStreamWrapper's "S3 is actually a local
      // file system. No really!" functionality.
      copy($path, "s3://$relative_path");
    }

    drupal_set_message($this->t('Copied all local %scheme files to S3.', ['%scheme' => $scheme]), 'status');
  }

  /**
   * {@inheritdoc}
   *
   * Scans a given directory.
   *
   * @param string $dir
   *   The directory to be scanned.
   *
   * @return array
   *   Array of file paths.
   */
  public function dirScan($dir) {
    $output = [];
    $files = scandir($dir);
    foreach ($files as $file) {
      $path = "$dir/$file";

      if ($file != '.' && $file != '..') {
        // In case they put their private root folder inside their public one,
        // skip it. When listing the private file system contents, $path will
        // never trigger this.
        if ($path == realpath(PrivateStream::basePath() ? PrivateStream::basePath() : '')) {
          continue;
        }

        if (is_dir($path)) {
          $output = array_merge($output, $this->dirScan($path));
        }
        else {
          $output[] = $path;
        }
      }
    }
    return $output;
  }

  /**
   * {@inheritdoc}
   *
   * Refreshes the metadata cache.
   *
   * Iterates over the full list of objects in the s3fs_root_folder within S3
   * bucket (or the entire bucket, if no root folder has been set), caching
   * their metadata in the database.
   *
   * It then caches the ancestor folders for those files, since folders are not
   * normally stored as actual objects in S3.
   *
   * @param array $config
   *   An s3fs configuration array.
   */
  public function refreshCache(array $config) {
    $s3 = $this->getAmazonS3Client($config);

    // Set up the iterator that will loop over all the objects in the bucket.
    $file_metadata_list = [];
    $iterator_args = ['Bucket' => $config['bucket']];
    if (!empty($config['root_folder'])) {
      // If the root_folder option has been set, retrieve from S3 only those
      // files which reside in the root folder.
      $iterator_args['Prefix'] = "{$config['root_folder']}/";

    }
    // Create an iterator that will emit all of the objects matching the
    // key prefix.
    $iterator = $s3->getIterator('ListObjectVersions', $iterator_args);

    // The $folders array is an associative array keyed by folder paths, which
    // is constructed as each filename is written to the DB. After all the files
    // are written, the folder paths are converted to metadata and written.
    $folders = [];
    // Start by gathering all the existing folders. If we didn't do this, empty
    // folders would be lost, because they'd have no files from which to rebuild
    // themselves.
    $existing_folders = \Drupal::database()->select('s3fs_file', 's')
      ->fields('s', ['uri'])
      ->condition('dir', 1, '=');
    foreach ($existing_folders->execute()->fetchCol(0) as $folder_uri) {
      $folders[$folder_uri] = TRUE;
    }

    // Create the temp table, into which all the refreshed data will be written.
    // After the full refresh is complete, the temp table will be swapped with
    // the real one.
    module_load_install('s3fs');
    $schema = s3fs_schema();
    try {
      \Drupal::database()->schema()->dropTable('s3fs_file_temp');
      \Drupal::database()->schema()->createTable('s3fs_file_temp', $schema['s3fs_file']);
    }
    catch (SchemaObjectExistsException $e) {
      // The table already exists, so we can simply truncate it to start fresh.
      \Drupal::database()->truncate('s3fs_file_temp')->execute();
    }

    foreach ($iterator as $s3_metadata) {
      $key = $s3_metadata['Key'];
      // The root folder is an impementation detail that only appears on S3.
      // Files' URIs are not aware of it, so we need to remove it beforehand.
      if (!empty($config['root_folder'])) {
        $key = str_replace("{$config['root_folder']}/", '', $key);
      }

      // Figure out the scheme based on the key's folder prefix.
      $public_folder_name = !empty($config['public_folder']) ? $config['public_folder'] : 's3fs-public';
      $private_folder_name = !empty($config['private_folder']) ? $config['private_folder'] : 's3fs-private';
      if (strpos($key, "$public_folder_name/") === 0) {
        // Much like the root folder, the public folder name must be removed
        // from URIs.
        $key = str_replace("$public_folder_name/", '', $key);
        $uri = "public://$key";
      }
      elseif (strpos($key, "$private_folder_name/") === 0) {
        $key = str_replace("$private_folder_name/", '', $key);
        $uri = "private://$key";
      }
      else {
        // No special prefix means it's an s3:// file.
        $uri = "s3://$key";
      }

      if ($uri[strlen($uri) - 1] == '/') {
        // Treat objects in S3 whose filenames end in a '/' as folders.
        // But don't store the '/' itself as part of the folder's uri.
        $folders[rtrim($uri, '/')] = TRUE;
      }
      else {
        // Only store the metadata for the latest version of the file.
        if (isset($s3_metadata['IsLatest']) && !$s3_metadata['IsLatest']) {
          continue;
        }
        // Files with no StorageClass are actually from the DeleteMarkers list,
        // rather then the Versions list. They represent a file which has been
        // deleted, so don't cache them.
        if (!isset($s3_metadata['StorageClass'])) {
          continue;
        }
        // Buckets with Versioning disabled set all files' VersionIds to "null".
        // If we see that, unset VersionId to prevent "null" from being written
        // to the DB.
        if (isset($s3_metadata['VersionId']) && $s3_metadata['VersionId'] == 'null') {
          unset($s3_metadata['VersionId']);
        }
        $file_metadata_list[] = $this->convertMetadata($uri, $s3_metadata);
      }
    }

    // Splits the data into manageable parts for the database.
    $chunks = array_chunk($file_metadata_list,  '10000');
    foreach ($chunks as $chunk) {
      // The event listener doesn't fire after the last page is done, so we have
      // to write the last page of metadata manually.
      $this->writeTemporaryMetadata($chunk, $folders);
    }

    // Now that the $folders array contains all the ancestors of every file in
    // the cache, as well as the existing folders from before the refresh,
    // write those folders to the DB.
    if ($folders) {
      $insert_query = \Drupal::database()->insert('s3fs_file_temp')
        ->fields(['uri', 'filesize', 'timestamp', 'dir', 'version']);
      foreach ($folders as $folder_uri => $ph) {
        $metadata = $this->convertMetadata($folder_uri, []);
        $insert_query->values($metadata);
      }
      // @todo: If this throws an integrity constraint violation, then the user's
      // S3 bucket has objects that represent folders using a different scheme
      // than the one we account for above. The best solution I can think of is
      // to convert any "files" in s3fs_file_temp which match an entry in the
      // $folders array (which would have been added in _s3fs_write_metadata())
      // to directories.
      $insert_query->execute();
    }

    // Swap the temp table with the real table.
    \Drupal::database()->schema()->renameTable('s3fs_file', 's3fs_file_old');
    \Drupal::database()->schema()->renameTable('s3fs_file_temp', 's3fs_file');
    \Drupal::database()->schema()->dropTable('s3fs_file_old');

    // Clear every s3fs entry in the Drupal cache.
    Cache::invalidateTags([S3FS_CACHE_TAG]);

    drupal_set_message($this->t('S3 File System cache refreshed.'));
  }

  /**
   * {@inheritdoc}
   *
   * Convert file metadata returned from S3 into a metadata cache array.
   *
   * @param string $uri
   *   The uri of the resource.
   * @param array $s3_metadata
   *   An array containing the collective metadata for the object in S3.
   *   The caller may send an empty array here to indicate that the returned
   *   metadata should represent a directory.
   *
   * @return array
   *   A file metadata cache array.
   */
  public function convertMetadata($uri, array $s3_metadata) {
    // Need to fill in a default value for everything, so that DB calls
    // won't complain about missing fields.
    $metadata = [
      'uri' => $uri,
      'filesize' => 0,
      'timestamp' => REQUEST_TIME,
      'dir' => 0,
      'version' => '',
    ];

    if (empty($s3_metadata)) {
      // The caller wants directory metadata.
      $metadata['dir'] = 1;
    }
    else {
      // The filesize value can come from either the Size or ContentLength
      // attribute, depending on which AWS API call built $s3_metadata.
      if (isset($s3_metadata['ContentLength'])) {
        $metadata['filesize'] = $s3_metadata['ContentLength'];
      }
      else {
        if (isset($s3_metadata['Size'])) {
          $metadata['filesize'] = $s3_metadata['Size'];
        }
      }

      if (isset($s3_metadata['LastModified'])) {
        $metadata['timestamp'] = date('U', strtotime($s3_metadata['LastModified']));
      }

      if (isset($s3_metadata['VersionId'])) {
        $metadata['version'] = $s3_metadata['VersionId'];
      }
    }
    return $metadata;
  }

  /**
   * Writes metadata to the temp table in the database.
   *
   * @param array $file_metadata_list
   *   An array passed by reference, which contains the current page of file
   *   metadata. This function empties out $file_metadata_list at the end.
   * @param array $folders
   *   An associative array keyed by folder name, which is populated with the
   *   ancestor folders of each file in $file_metadata_list.
   */
  private function writeTemporaryMetadata(array &$file_metadata_list, array &$folders) {
    if ($file_metadata_list) {
      $insert_query = \Drupal::database()->insert('s3fs_file_temp')
        ->fields(['uri', 'filesize', 'timestamp', 'dir', 'version']);

      foreach ($file_metadata_list as $metadata) {
        // Write the file metadata to the DB.
        $insert_query->values($metadata);

        // Add the ancestor folders of this file to the $folders array.
        $uri = \Drupal::service('file_system')->dirname($metadata['uri']);
        $root = \Drupal::service('file_system')->uriScheme($uri) . '://';
        // Loop through each ancestor folder until we get to the root uri.
        while ($uri != $root) {
          $folders[$uri] = TRUE;
          $uri = \Drupal::service('file_system')->dirname($uri);
        }
      }
      $insert_query->execute();
    }

    // Empty out the file array, so it can be re-filled by the next request.
    $file_metadata_list = [];
  }

}
