@api

Feature: Main menu
  In order to navigate the website
  As a user
  I need to be able to understand and use the main menu.

  Scenario: Viewing menu items
    Given I am on the homepage
    Then I should see the link "Help and advice" in the "main_menu" region

  Scenario: Following menu items
    Given I am on the homepage
    When I follow "Help and advice"
    Then I should see the heading "Help and advice"
