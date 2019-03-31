@api

Feature: Publication date field
  In order to properly sort content
  As a site editor
  I need to be able to add the appropriate publication date to blog posts and news items.

  Background:
    Given I am logged in as a user with the "Content author" role

    @editing @blogs @date
    Scenario: Publication date field exists on blogs
      When I am on "node/add/blog_post"
      Then I see the text "Publication date"

    @editing @news @date
    Scenario: Publication date field exists on news items
      When I am on "node/add/news_item"
      Then I see the text "Publication date"
