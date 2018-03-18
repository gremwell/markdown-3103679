# Markdown

This module provides Markdown integration for Drupal. The Markdown syntax is
designed to co-exist with HTML, so you can set up input formats with both HTML
and Markdown support. It is also meant to be as human-readable as possible when
left as "source".

There are several types of PHP Markdown parsing libraries. This module
currently supports the following:

- [thephpleague/commonmark] (required, [Drupal Standard])
- [erusev/parsedown]
- [michelf/php-markdown]

This module provides a text format filter that converts Markdown to HTML based
on the [CommonMark] spec via [thephpleague/commonmark] PHP library, created and
maintained by [The League of Extraordinary Packages].

## Try out a demonstration!
https://commonmark.unicorn.fail (@todo)

To see a full list of "long tips" provided by this filter, visit:
https://commonmark.unicorn.fail/filter/tips

## Requirements
- **PHP >= 5.6** - This is a hard requirement due to [thephpleague/commonmark].

## Soft Requirements
This modules supports the following methods for installing the necessary PHP
libraries and autoloading the module and library PSR-4 classes. You must choose
one of the following methods:

- **Composer**  
  Composer is the preferred method for installing PHP libraries and autoloading
  the module and library PSR-4 classes. Works with [Composer Manager] and
  [Drupal Composer Packagist].
- **Registry Autoload or X Autoload**  
  This module supports using the [Registry Autoload] module or the [X Autoload]
  module. If you use either of these modules, you must also install the
  necessary PHP libraries. You can do this automatically using the following
  Drush command or by manually placing the necessary PHP libraries in
  `sites/*/libraries`:  
    
  ```sh
  drush markdown-download
  ```

## Installation
If you are comfortable with composer that is the best way to install both PHP
Markdown and CommonMark. They will then be autoloaded just like other parts of
Drupal 8.

The old way of installation in the libraries directory is only supported for PHP
Markdown. The libraries module is then needed to load the library.

1. Download and install the [Libraries](https://www.drupal.org/project/libraries)
2. Download the PHP Markdown library from
   https://github.com/michelf/php-markdown/archive/lib.zip, unpack it and place
   it in the `libraries` directory in Drupal root folder, if it doesn't exist
   you need to create it.

Make sure the path becomes
`/libraries/php-markdown/Michelf/MarkdownExtra.inc.php`.

## CommonMark Extensions
- **Enhanced Links** - _Built in, enabled by default_  
  Extends CommonMark to provide additional enhancements when rendering links.
- **@ Autolinker** - _Built in, disabled by default_  
  Automatically link commonly used references that come after an at character
  (@) without having to use the link syntax.
- **# Autolinker** - _Built in, disabled by default_  
  Automatically link commonly used references that come after a hash character
  (#) without having to use the link syntax.
- **[CommonMark Attributes Extension]**
  Adds syntax to define attributes on various HTML elements inside a CommonMark
  markdown document. To install, enable the `commonmark_attributes` sub-module.
- **[CommonMark Table Extension]**  
  Adds syntax to create tables in a CommonMark markdown document.  To install,
  enable the `commonmark_table` sub-module.

## Programmatic Conversion
In some cases you may need to programmatically convert CommonMark Markdown to
HTML. In procedural functions, you can accomplish this in the following manner:
```php
<?php
use \Drupal\markdown\Markdown;

function my_module_callback_function($markdown) {
  return ['#markup' => Markdown::create()->parse($markdown)];  
}


$markdown = '# Hello World!';
$build = my_module_callback_function('# Hello World!');
// Returns: ['#markup' => '<h1>Hello World!</h1>']
```

If you need to parse Markdown in other services, inject it as a dependency:
```php
<?php

use \Drupal\markdown\Markdown;  

class MyService {
  
  /**
   * The Markdown service.
   * 
   * @var \Drupal\markdown\Markdown
   */
  protected $markdown;
  
  /**
   * MyService constructor. 
   */
  public function __construct(Markdown $markdown) {
    $this->markdown = $markdown;
  }
  
  /**
   * MyService renderer. 
   */
  public function render(array $items) {
    $output = '';
    foreach ($items as $markdown) {
      $output .= $this->markdown->parse($markdown);
    }
    return ['#markup' => $output];
  }
}
```

Or if using it in classes where modifying the constructor may prove difficult,
use the `MarkdownTrait`:
```php
<?php

use \Drupal\markdown\Traits\MarkdownTrait;  

class MyController {
  
  use MarkdownTrait;
  
  /**
   * MyService renderer. 
   */
  public function render(array $items) {
    $output = '';
    foreach ($items as $markdown) {
      $output .= $this->markdown()->parse($markdown);
    }
    return ['#markup' => $output];
  }
}
```

## Markdown editor
If you are interested in a Markdown editor please check out
the Markdown editor for BUEditor module.

<http://drupal.org/project/markdowneditor>

## Notes
Markdown may conflict with other input filters, depending on the order
in which filters are configured to apply. If using Markdown produces
unexpected markup when configured with other filters, experimenting with
the order of those filters will likely resolve the issue.

Filters that should be run before Markdown filter includes:

- Code Filter
- GeSHI filter for code syntax highlighting

Filters that should be run after Markdown filter includes:

- Typogrify

The "Limit allowed HTML tags" filter is a special case:

For best security, ensure that it is run after the Markdown filter and
that only markup you would like to allow via HTML and/or Markdown is
configured to be allowed.

If you on the other hand want to make sure that all converted Markdown
text is preserved, run it before the Markdown filter. Note that blockquoting
with Markdown doesn't work in this case since "Limit allowed HTML tags" filter
converts the ">" in to "&gt;".

## Smartypants Support

This module is a continuation of the Markdown with Smartypants module.
It only includes Markdown support and it is now suggested that you use
Typogrify module if you are interested in Smartypants support.

<http://drupal.org/project/typogrify>

[CommonMark]: http://commonmark.org/
[CommonMark Attributes Extension]: https://github.com/webuni/commonmark-attributes-extension
[CommonMark Table Extension]: https://github.com/webuni/commonmark-table-extension
[Composer Manager]: https://www.drupal.org/project/composer_manager
[Drupal Composer Packagist]: https://packagist.drupal-composer.org/packages/drupal/commonmark
[Drupal Standard]: https://www.drupal.org/project/coding_standards/issues/2952616
[erusev/parsedown]: https://github.com/erusev/parsedown
[michelf/php-markdown]: https://github.com/michelf/php-markdown
[thephpleague/commonmark]: https://github.com/thephpleague/commonmark
[Registry Autoload]: https://www.drupal.org/project/registry_autoload
[The League of Extraordinary Packages]: http://commonmark.thephpleague.com/
[X Autoload]: https://www.drupal.org/project/xautoload