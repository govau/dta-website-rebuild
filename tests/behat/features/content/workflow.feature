@api

Feature: Workflow
  In order to provide users with basic content
  As an site owner
  I need to be able to create, manage and maintain content.

  Background:
    Given "page" content:
    | title       | field_introduction       | field_body       | field_summary  | status | moderation_state |
    | test page 1 | test page 1 introduction | test page 1 body | test summary 1 | 0      | draft            |
    | test page 2 | test page 2 introduction | test page 2 body | test summary 2 | 0      | draft            |
    | test page 3 | test page 3 introduction | test page 3 body | test summary 3 | 0      | technical_review |
    | test page 4 | test page 4 introduction | test page 4 body | test summary 4 | 0      | ready_to_publish |
    | test page 5 | test page 5 introduction | test page 5 body | test summary 5 | 0      | ready_to_publish |

    And "media_release" content:
    | title       | field_body       | field_summary  | status | moderation_state |
    | test page 6 | test page 6 body | test summary 6 | 0      | draft            |

    @workflow @media-release-author

    Scenario: Create new content and publish
      Given I am logged in as a user with the "Media release author" role
      When I am on "/media-release/test-page-6"
      And I follow "Edit"
      And I select "Published" from "Change to"
      And I press "Preview"
      Then I should see the text "test page 6 body"
      And I follow "Back to content editing"
      And I press "Save"
      And the response status code should be 200

    @workflow @content-author

    Scenario: Create new content and submit for technical review
      Given I am logged in as a user with the "Content author" role
      When I am on "test-page-1"
      And I select "Technical review" from "Change to"
      And I press "Apply"
      Then I should see the text "The moderation state has been updated"

    @workflow @restricted-author

    Scenario: Create new content and submit for technical review as restricted author
      Given I am logged in as a user with the "Restricted author" role
      When I am on "test-page-2"
      And I select "Technical review" from "Change to"
      And I press "Apply"
      Then I should see the text "The moderation state has been updated"

    @workflow @technical-reviewer

    Scenario: Move content into ready to publish
      Given I am logged in as a user with the "Technical reviewer" role
      When I am on "test-page-3"
      And I select "Ready to publish" from "Change to"
      And I press "Apply"
      Then I should see the text "The moderation state has been updated"

    @workflow @content-approver

    Scenario: Publish content
      Given I am logged in as a user with the "Content approver" role
      When I am on "test-page-4"
      And I select "Published" from "Change to"
      And I press "Apply"
      Then I should see the text "The moderation state has been updated"

    @workflow @restricted-approver

    Scenario: Publish content as restricted approver
      Given I am logged in as a user with the "Restricted approver" role
      When I am on "test-page-5"
      And I select "Published" from "Change to"
      And I press "Apply"
      Then I should see the text "The moderation state has been updated"
