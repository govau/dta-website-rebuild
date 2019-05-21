@api

Feature: Restricted approvers
  In order to provide users with basic content
  As a site owner
  I need to be able to approve basic content.

  Background:
    Given "page" content:
    | title       | field_introduction       | field_body       | field_summary  | status | moderation_state | nid  |
    | test page 1 | test page 1 introduction | test page 1 body | test summary 1 | 1      | published        | 9999 |

    @content @restricted-approver

    Scenario: Edit existing content
      Given I am logged in as a user with the "Restricted approver" role
      When I go to "node/9999/edit"
      Then I should see the heading "Edit Page test page 1"
