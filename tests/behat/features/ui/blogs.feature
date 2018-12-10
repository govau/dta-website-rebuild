Feature: Blogs and news items
  In order to learn more about the DTA's products, services and events
  As a user
  I need to be able to view the blogs and news items.

  @api
  Scenario: Viewing tag pages/taxonomy terms
    Given I am viewing a "Tags" term with the name "test"
    Then I should see the text "test" in the "content" region
