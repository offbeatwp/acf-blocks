# ACF Blocks for OffbeatWP Components

ACF offers since version 5.8 the possibilty the create blocks. This service makes the OffbeatWP components available as blocks in the Wordpress editor (gutenberg)

Install by running this command from the root of your OffbeatWP Theme:

```bash
composer require offbeatwp/acf-blocks
```

Next add the following line to your `config/services.php` file:

```php
OffbeatWP\AcfBlocks\Service::class,
```

## Installing the "Block" Component

Run the following command from somewhere in your wordpress installation.

```bash
wp acf-blocks:install
```
## Enable component for blocks

Add within the `support` array in the settings method `editor` like:

```php
public static function settings()
{
    return [
        ...
        'supports'   => ['editor'],
        ...
    ];
}
```

