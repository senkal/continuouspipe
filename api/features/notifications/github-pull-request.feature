Feature:
  In order to have a feedback on deployed environments
  As a developer
  I want to have the environment addresses commented on my pull-requests

  Background:
    Given the created GitHub comment will have the ID 1

  Scenario: The addresses are commented when the deployment is successful
    Given there is 1 application images in the repository
    And a tide is started with a deploy task
    And the pull-request #1 contains the tide-related commit
    When the deployment succeed
    Then the addresses of the environment should be commented on the pull-request

  Scenario: The addresses are automatically commented if the deployment is already done
    Given a deployment for a commit "123" on branch "foo" is successful
    When a pull-request is created from branch "foo" with head commit "123"
    Then the addresses of the environment should be commented on the pull-request

  Scenario: Replace the previous comment if already exists on the PR
    Given there is 1 application images in the repository
    And a tide is started with a deploy task
    And the pull-request #1 contains the tide-related commit
    And a comment identified "1234" was already added
    When the deployment succeed
    Then the addresses of the environment should be commented on the pull-request
    And the comment "1234" should have been deleted
