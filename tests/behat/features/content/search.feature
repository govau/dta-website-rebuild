@api

Feature: Search
  In order for users to find content
  As a site owner
  I need to be able to display content on the search results page.

  Background:
    Given I am an anonymous user
    And "page" content:
    | title       | field_introduction       | field_body       | field_summary  | status | moderation_state |
    | test page 1 | test page 1 introduction | test page 1 body | test summary 1 | 1      | published        |
    And "book" content:
    | title       | field_body       | field_summary  | status | moderation_state |
    | test page 2 | test page 2 body | test summary 2 | 1      | published        |

    And I run cron

    @content @search
    Scenario: Search for content
      When I go to "search?keys=test"
      Then I should see the text "test summary 1"
      And I should see the text "test summary 2"
      And I should see 2 "h3 a" elements
