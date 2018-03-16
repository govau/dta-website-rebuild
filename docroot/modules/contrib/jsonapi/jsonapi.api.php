<?php

/**
 * @file
 * Documentation related to JSON API.
 */

/**
 * @defgroup jsonapi_normalizer_architecture JSON API Normalizer Architecture
 * @{
 *
 * @section resources Resources
 * The unit of data in the JSON API spec is a "resource", and relationships
 * between those units of data are called "relationships". The Drupal module
 * that implements JSON API exposes every entity as a resource, every entity
 * bundle as a resource type and every entity reference field as a relationship.
 *
 * While it is theoretically possible to expose arbitrary data as resources, the
 * decision to limit to only (config and content) entities means that all
 * relationships between entities (resources) and entity types (resource types)
 * are available automatically, without the need for another abstraction layer.
 *
 * The JSON API module can be summarized as just that: logic that exposes Drupal
 * entities according to the JSON API spec.*
 *
 *
 * @section normalizers Normalizers
 * The JSON API module reuses as many of Drupal core's Serialization module's
 * normalizers as possible.
 *
 * Since the JSON API module follows the http://jsonapi.org/ spec, which
 * requires special handling for resources (entities), relationships between
 * resources (entity references) and resource IDs (entity UUIDs), it has no
 * choice but to override the Serialization module's normalizers for entities,
 * fields and entity reference fields.
 *
 * This also means that contributed/custom modules that provide additional field
 * types need to implement normalizers not at the field level ("FieldType"
 * plugins), but at a level below that ("DataType" plugins). Otherwise they will
 * not have any effect.
 *
 * A benefit of implementing normalizers at that lower level is that they then
 * work automatically for both the JSON API module and core's REST module.
 *
 *
 * @section api API
 * The JSON API module provides a HTTP API, that follows the jsonapi.org spec.
 *
 * The JSON API module does not provide a PHP API to modify its behavior. It is
 * designed to be "zero-configuration".
 *
 * - Adding new resources/resource types is unsupported: all entities/entity
 *   types are exposed automatically. If you want to expose more data via JSON
 *   API, make sure they're entities. See the "Resources" section.
 * - Customizing the normalization of fields is not supported: only normalizers
 *   for "DataType" plugins are supported (a level below fields).
 *
 * The JSON API module does provide a PHP API to generate JSON API
 * representations of entities:
 * @code
 * \Drupal::service('jsonapi.entity.to_jsonapi')->serialize($entity)
 * @endcode
 *
 *
 * @section bc Backwards Compatibility
 * PHP API: there is no PHP API, which means this module's implementation
 * details are free to change at any time.
 * (Also note that normalizers are internal implementation details: they are
 * services due to the design of the Symfony Serialization component, not
 * because the JSON API module wanted to expose services.)
 *
 * HTTP API: URLs and JSON response structures are considered part of this
 * module's public API. However, inconsistencies with the jsonapi.org
 * specification will be considered bugs. Fixes which bring the module into
 * compliance with the specification are not guaranteed to be backwards
 * compatible. What this means for developing consumers of the HTTP API is that
 * clients should be implemented from the specification first and foremost to
 * mitigate implicit dependencies on implementation details specific to this
 * module.
 *
 * To help develop compatible clients, every response indicates the version of
 * the JSON API specification used under its "jsonapi" key. Future releases
 * *may* increment the minor version number if the module implements the
 * features of a later specification. The specification stipulates that future
 * versions *will* remain backwards compatible as only additions may be
 * released.
 *
 * @}
 */
