Feature: Developer runs actions to save variables in file
  As a Developer
  I want to save variables in files

  Scenario: Saving version
    Given the file "stamp.yml" contains:
      """
      actions:
        -
          name: parse_variable_from_file
          parameters:
            regex:    '/"version" *: *"(?P<version>[^"]+)"/'
            filename: 'metadata.json'
        -
          name: patch_up
          parameters:
            variable: 'version'
        -
          name: save_variable_to_file
          parameters:
            filename: 'metadata.json'
            variable: 'version'
            src:      '/"version" *: *"(?P<version>[^"]+)"/'
            dest:     '"version": "{{ version }}"'
      """
    And the file "metadata.json" contains:
      """
      {
        "version": "6.248.112",
      }
      """
    When I run command "raw:run" in verbose mode
    Then the output should contain:
      """
      parse_variable_from_file["filename"="metadata.json"]["version"="6.248.112"]
      patch_up["version"="6.248.113"]
      save_variable_to_file["version": "6.248.113"]
      """
