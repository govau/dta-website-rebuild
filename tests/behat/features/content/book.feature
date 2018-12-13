@api

Feature: Book Pages
  In order to understand which book I am reading and navigate to the top level.
  As a user
  I need to be able to see the book title with a link at the bottom of each book page.

  Scenario: Create book
    Given I am logged in as a user with the "Content editor" role
    And I am on "node/add/book"
    When I fill in "Test Book" for "Title"
    And I fill in "Test book summary" for "Summary"
    And I fill in "Test book placeholder text" for "Body"
    And I select "- Create a new book -" from "Book"
    And I select "Published" from "Save as"
    And I press the "Save" button
    And I am on "admin/structure/book"
    And I follow "Test Book"
    Then I should see the heading "Test Book"

  Scenario: Add child book
    Given I am logged in as a user with the "Content editor" role
    And I am on "node/add/book"
    When I fill in "Test Book page" for "Title"
    And I fill in "Test book summary" for "Summary"
    And I fill in "Test book placeholder text" for "Body"
    And I select "- Create a new book -" from "Book"
    And I select "Published" from "Save as"
    And I press the "Save" button
    And I am on "admin/structure/book"
    And I follow "Test Book"
    And I click "Add child page"
    And I fill in "Test Book page" for "Title"
    And I fill in "Test book page summary" for "Summary"
    And I fill in "Test book page placeholder text" for "Body"
    And I select "Published" from "Save as"
    And I press the "Save" button
    Then I should see the heading "Test Book page"

  Scenario: View book
    Given I am an anonymous user
    When I am on "test-book"
    Then I should get a "403" HTTP response
