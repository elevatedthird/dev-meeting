<?php
/**
 * Implements hook_library_info_alter().
 */
function themekit_library_info_alter(&$libraries, $extension) {
  // Get the path of the theme where this function is being called
  $theme_name = basename(__FILE__, '.theme');
  // Get the path of the theme where this function is being called
  $theme_path = drupal_get_path('theme', $theme_name);
  // Alter only the library definitions of the current theme.
  if ($extension == $theme_name) {

    $partial_libraries = [];

    $partials_file = $theme_path . '/partials.yml';
    if (file_exists($partials_file)) {
      try {
        $partial_libraries = Yaml::decode(file_get_contents($partials_file)) ?? [];
      }
      catch (InvalidDataTypeException $e) {
        // Rethrow a more helpful exception to provide context.
        throw new InvalidLibraryFileException(sprintf('Invalid library definition in %s: %s', $partials_file, $e->getMessage()), 0, $e);
      }
    }

    $libraries = NestedArray::mergeDeep($libraries, $partial_libraries);
  }
}
