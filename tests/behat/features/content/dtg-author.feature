@api

Feature: DTG authors
  In order to provide users with digital transformation guide content
  As a digital transformation guide author
  I need to be able to create, manage and maintain content.

  Background:
    Given I am logged in as a user with the "Digital Transformation Guide author" role

    @content @dtg-author
    Scenario: Create content
      When I follow "Content"
      And I follow "Add content"
      Then I should see 5 ".admin-list a" elements

    @menus @dtg-author
    Scenario: Edit menus
      When I follow "Structure"
      And I follow "Menus"
      Then I should see 1 "td.menu-label" element
