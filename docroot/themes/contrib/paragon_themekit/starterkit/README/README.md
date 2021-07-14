# Partials Info
  Partials libraries and webpack entries can now be automatically generated.
  The goal is to only load our css when we need and
  reduce the work that goes into adding a piece of functionality/component.
## Requirements
  - https://www.drupal.org/project/components
  - Add the hook in the hook_library_info_alter.php to your theme.
  - You will need the script from line 16-62 in the webpack file and those packages in the required lines to your package.json
  - You will also want to take a look at lines 63-85 and 236-247 in the webpack file.
  - Add this to the info.yml
```
components:
  namespaces:
    partials: partials
```
This allows for extending:

`{% extends "@partials/region/region.html.twig" %}`

### How it works
  Anything in the partials directory will be grabbed when webpack runs. It will check
to see if a directory has a css or js files. If so the script will now generate a library name
based on the path (example: path = /partials/region/header/header.scss => library name = region--header). All the css and js
files will be listed in that library. The previous example will have just the header.css (after compile) added to its listing.

### Notes
  #### macros
  - Marcros can be in the partials dir for automatic library generation. Macros are pieces of functionality that the same everytime, but you may use across multiple components or twig files.
  - See example
  - https://twig.symfony.com/doc/1.x/tags/macro.html
  #### libraries
  - The library name will become the path of directories with -- instead of /. /partials/region/header/header => region--header
  - See the examples dir.
  - You can still add libraries to the library.yml if needed.
  #### webpack
  - Css and js files can be named what you want, but I'd stick with the dir name.
  - Js files will have "-js" added to the end of the file name.
  #### twig
  - Make sure the attach_library declarations match the file structure.
  - Make use of dynamic twig vars to generate the library string.
  - Prefix Library string with the root level partial dir for clarity.
  - You can have multiple declarations, and they will only be attached when needed.
  - See the examples dir.
  #### config.js files
  - See component/simple example
  - Here you can declare extra entries to the js or css listings of the library that won't be automatically added (so assets not in this directory).
  - You can use this just like a json version of the yml file, and it will merge into the library on compile.
  - Also, a good place to add dependencies
  - Add any other valid properties.

### Pattern
When adding a root level (new partial) directory

### Examples
The examples dir has starting points or embodies the patterns described above.
#### Node (could be Media)
  The example here is a really good starting point/pattern for media and node types (or content entities in general). The wrapper adds all the "attach_library" declarations
and then all the other files extend that.
  - This structure will allow you to have general styling for a display view and refine it per node type. Notice full.scss, that will act on all full displays if you want (teaser could be a better example).
  - DON'T put twig files in the type dir. Only dir > scss file (example partials/node/type/basic/basic.scss). So could be useful for global basic page styling that doesn't matter on display.
