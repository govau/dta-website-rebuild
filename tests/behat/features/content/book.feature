@api

Feature: Book Pages
  In order to understand which book I am reading and navigate to the top level.
  As a user
  I need to be able to see the book title with a link at the bottom of each book page.

  Background:
    Given "book" content:
      | title            | field_summary          | field_body   | moderation_state | bid        |
      | Test Book Root   | Test book root summary | placeholder  | published        |                |
      | Test Book Page   | Test book page summary | placeholder  | published        | Test Book Root |
    When I am logged in as a user with the "Content editor" role
    And I am on "test-book-page"

    Scenario: Book exists
      Then I see the heading "Test Book Page"

    Scenario: Edit book
      When I click "Edit" in the "content" region
      Then I should see the heading "Edit Book page Test Book Page"

  Scenario: View book
    Given I am an anonymous user
    When I am on "test-book-page"
    Then I should not see the link "Edit"
