@api

Feature: ToC
  In order to allow users to navigate through pages
  As a content author
  I need to be able to create, manage and maintain tables-of-contents.

  Background:
    Given "page" content:
    | title       | field_introduction       | field_body       | field_summary  | status | moderation_state |
    | test page 1 | test page 1 introduction | test page 1 body | test summary 1 | 1      | published        |
    | test page 2 | test page 2 introduction | test page 2 body | test summary 2 | 1      | published        |
    And I am not logged in

    @toc

    Scenario: Check for no table of contents
      When I am logged in as a user with the "Content author" role
      When I am on "/test-page-1"
      And I follow "Edit"
      And I fill in "Body content" with "<h2>Heading 1</h2><h2>Heading 2</h2"
      And I press "Save"
      Then I should not see a "nav.au-inpage-nav-links" element

    @toc

    Scenario: Check for a table of contents
      When I am logged in as a user with the "Content author" role
      And I am on "/test-page-2"
      And I follow "Edit"
      And I fill in "Body content" with "[toc]<h2>Heading 1</h2><h2>Heading 2</h2"
      And I press "Save"
      Then I should see 1 "nav.au-inpage-nav-links" element
