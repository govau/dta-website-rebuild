@api

Feature: Social image fields
  In order to increase engagement with our social content
  As a site editor
  I need to be able to add the appropriate social images to blog posts, news items, pages and landing pages.

  Background:
    Given I am logged in as a user with the "Content author" role

    @social @editing @blogs
    Scenario: Social images fields exist on blogs
      When I am on "node/add/blog_post"
      Then I see the text "Open Graph image"
      And I see the text "Twitter image"

    @social @editing @news
    Scenario: Social images fields exist on news items
      When I am on "node/add/news_item"
      Then I see the text "Open Graph image"
      And I see the text "Twitter image"

    @social @editing @page
    Scenario: Social images fields exist on pages
      When I am on "node/add/page"
      Then I see the text "Open Graph image"
      And I see the text "Twitter image"

    @social @editing @landing_page_1
    Scenario: Social images fields exist on landing pages (level 1)
      When I am on "node/add/landing_page"
      Then I see the text "Open Graph image"
      And I see the text "Twitter image"

    @social @editing @landing_page_2
    Scenario: Social images fields exist on landing pages (level 2)
      When I am on "node/add/landing_page_level_2"
      Then I see the text "Open Graph image"
      And I see the text "Twitter image"
