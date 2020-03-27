Feature: Home Page Layout
  Ensure the home page layout is rendering the blocks in the correct order

  Scenario: View the homepage layout
    Given I am on the homepage
    Then I should see the heading "Get help and advice" in the content region
    Then I should see the heading "News, blogs and media releases" in the content region
    Then I should see the heading "Our projects" in the content region

    
