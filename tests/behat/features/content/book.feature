@api

Feature: Book Pages
  In order to provide large amounts of related content
  As a content editor
  I need to be able to create, manage and maintain books.

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
