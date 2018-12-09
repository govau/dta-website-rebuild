@api

Feature: Book Pages
  In order to understand which book I am reading and navigate to the top level.
  As a user
  I need to be able to see the book title with a link at the bottom of each book page.

  Scenario: Ensure DTS has correct title and link
    Given I am an anonymous user
    When I follow "Digital Transformation Strategy" in the "main_menu" region
    And I click "Read the strategy" in the "highlight" region
    And I click "Digital Transformation Strategy" in the "book_navigation" region
    Then I should see the heading "Digital Transformation Strategy"

  Scenario: Ensure the Platform Strategy has the correct title and link
    Given I am logged in as a user with the "Content editor" role
    When I follow "Our projects" in the "main_menu" region
    And I click "Strategies"
    And I click "Digital Service Platforms Strategy"
    And I click "Foreword"
    And I click "Digital Service Platforms Strategy" in the "book_navigation" region
    Then I should see the heading "Digital Service Platforms Strategy"
