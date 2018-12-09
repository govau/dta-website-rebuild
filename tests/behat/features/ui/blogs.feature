@api

Feature: Blogs and news items
  In order to learn more about the DTA's products, services and events
  As a user
  I need to be able to view the blogs and news items.

  Scenario: Viewing tag pages/taxonomy terms
    Given I am viewing a "Tags" term with the name "ICT procurement"
    Then I should see the text "ICT procurement" in the "content" region
    And I should see the most recent item first
