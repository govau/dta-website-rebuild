@api

Feature: Print content
In order print content
As an anonymous user

Background:
  Given "book" content:
  | title          | field_body       | field_summary     | moderation_state | status |
  | Test book      | Test book body   | Test book summary | published        | 1      |
  | Test book page | Test book body 2 | Test book summary | published        | 1      |
  And I am logged in as a user with the "Content editor" role
  And I am on "test-book"
  When I click "Edit" in the "content" region
  And I select "- Create a new book -" from "Book"
  And I press "Save"
  And I am on "test-book-page"
  And I click "Edit" in the "content" region
  And I select "Test book" from "Book"
  And I press "Save"

  @anon @book @print
  Scenario: Print book
    Given I am an anonymous user
    And I am on "test-book"
    When I follow "Printer-friendly version"
    Then I should see the heading "Test book"
    And I should see "Test book body"
    And I should see the heading "Test book page"
    And I should see "Test book body 2"