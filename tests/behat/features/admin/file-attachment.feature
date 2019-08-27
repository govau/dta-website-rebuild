@api

Feature: File attachment
  In order to increase engagement with our media releases
  As a media release author
  I need to be able to add file attachments to media releases easily.

  Background:
    Given I am logged in as a user with the "Media release author" role
    And "media_release" content:
    | title     | field_body     | field_summary | status | moderation_state |
    | test page | test page body | test summary  | 0      | draft            |

    @editing @media-release @fields
    Scenario: File attachment field exist on media releases
      When I am on "node/add/media_release"
      Then I see the text "File attachment"
      And I see the button "Upload"

    @editing @media-release @fields
    Scenario: Upload files
      When I am on "node/add/media_release"
      And I attach the file "test-document.docx" to "edit-field-attachments-0-upload"
      And I attach the file "test-document.pdf" to "edit-field-attachments-0-upload"
