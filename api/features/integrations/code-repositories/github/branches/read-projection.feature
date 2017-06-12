Feature:
  In order to display the branches of a repository
  As a system
  I want to write a read projection

  Background:
    Given I am authenticated as "samuel"
    And the team "samuel" exists
    And the user "samuel" is "USER" of the team "samuel"
    And the GitHub account "sroze" have the installation "0000"
    And the token of the GitHub installation "0000" is "1234"
    And the GitHub repository "bar" exists
    And I have a flow with UUID "d7825625-f775-4ab9-b91c-b93813871bc7"

  Scenario: It creates the read model for all branches
    Given I have a "continuous-pipe.yml" file in my repository that contains:
    """
    tasks:
        images:
            build: ~

        deployment:
            deploy:
                cluster: foo
                services: []

    """
    And the following branches exists in the github repository:
      | name    |
      | master  |
      | develop |
    When the commit "12345" is pushed to the branch "master"
    Then the branch "master" for the flow "d7825625-f775-4ab9-b91c-b93813871bc7" should be saved to the permanent storage of views
    And the branch "develop" for the flow "d7825625-f775-4ab9-b91c-b93813871bc7" should be saved to the permanent storage of views

  Scenario: It creates the read model for all branches
    Given I have a "continuous-pipe.yml" file in my repository that contains:
    """
    tasks:
        images:
            build: ~

        deployment:
            deploy:
                cluster: foo
                services: []

    """
    And the following branches exists in the github repository and are paginated in the api response:
      | name    |
      | master  |
      | develop |
    When the commit "12345" is pushed to the branch "master"
    Then the branch "master" for the flow "d7825625-f775-4ab9-b91c-b93813871bc7" should be saved to the permanent storage of views
    And the branch "develop" for the flow "d7825625-f775-4ab9-b91c-b93813871bc7" should be saved to the permanent storage of views
