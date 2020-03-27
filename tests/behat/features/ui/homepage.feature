Feature: Home Page
  Ensure the home page layout is rendering the blocks in the correct order

  Scenario: View the homepage content
    Given I am on the homepage
    Then I should see "homepageleft" block
    Then I should see "homepageright" block
    Then I should see "help_and_advice" block
    Then I should see "help_and_advice" block
    Then I should see "news_and_blogs_facets" block
    Then I should see "homepagebottom" block
