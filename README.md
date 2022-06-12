Backblaze B2 API for Symfony
============================

📼 Backblaze B2 API client services for Symfony projects

## Installation

To install this bundle, run the following in your Symfony project directory:

```bash
$ composer require steppinghat/backblaze-b2-bundle
```

Then register the bundle in `config/bundles.php`

```php
<?php

return [
    // ...
    SteppingHat\BackblazeB2\BackblazeB2Bundle::class => ['all' => true]
]
```

And lastly, create a config file in `config/packages` called `backblaze_b2.yaml`

```yaml
backblaze_b2:
  account_id: '%env(BACKBLAZE_MASTER_ACCOUNT_ID)%'
  application_id: '%env(BACKBLAZE_MASTER_APPLICATION_ID)%'
  application_secret: '%env(BACKBLAZE_MASTER_APPLICATION_KEY)%'
  token_cache_directory: ~
```

## Configuration

### Backblaze API keys
Before setting off, you'll need to create a key by going to [App Keys](https://secure.backblaze.com/app_keys.htm) in
your Backblaze Account.

_**Not all keys are created equal!**_

You _can_ use an application key, however the capabilities are very limited in comparison to the master application key.
Please bare this in mind when configuring the bundle.

### Caching Tokens

In order to both reduce the number of API calls made, and speed up the overall turnaround time, we can optionally cache
tokens to disk. This means that instead of requesting the authentication token needed to carry out requests every time,
we use one from an earlier call that we've cached. These tokens last ~12 hours before they expire and a new one is
needed.

While is can be preferable, some might not like the idea of keeping a token saved to disk for security reasons. If your
system is well secured and this isn't as much of a risk, turn this on by setting `tokenCacheDirectory` to a valid
directory in that we have permissions to write to.

This cache is not cleared by running `php bin/console cache:clear`

## Usage

For all basic usage of the API, simply use the `BackblazeClient` class in your service.

```php
<?php

use SteppingHat\BackblazeB2\Client\BackblazeClient;

class ExampleService {

    protected BackblazeClient $client;

    public function __construct(BackblazeClient $client) {
        $this->client = $client;    
    }
    
}
```

- [Create a bucket](#create-a-bucket)
- [List all buckets](#list-all-buckets)
- [Update a bucket](#update-a-bucket)
- [Delete a bucket](#delete-a-bucket)
- [List all files](#list-all-files)
- [Check if a file exists](#check-if-a-file-exists)
- [Get file info](#get-file-info)
- [Delete a file](#delete-a-file)
- [Upload a file](#upload-a-file)
- [Download a file](#download-a-file)

### Create a bucket
_Requires the `writeBuckets` capability_

```php
$bucket = $client->createBucket('bucketName', Bucket::TYPE_PRIVATE);
```

### List all buckets
_Requires the `listBuckets` capability_

```php
$buckets = $client->listBuckets();
```

### Update a bucket
_Requires the `writeBuckets` capability_

```php
$bucket = $client->updateBucket($bucket, Bucket::TYPE_PUBLIC);
```

### Delete a bucket
_Requires the `deleteBuckets` capability_

```php
$client->deleteBucket($bucket);
```

### List all files
_Requires the `listFiles` capability_

```php
// List all files across all buckets
$files = $client->listFiles();

// List all files in a specific bucket
$files = $client->listFiles($bucket);

// Search for a specific file
$files = $client->listFiles($bucket, 'animals/dogs/floof.png');

// Search for all files matching a prefix
$files = $client->listFiles($bucket, null, 'animals/dogs/');
```

### Check if a file exists
_Requires the `listFiles` capability_

```php
if($client->fileExists($bucket, 'animals/dogs/doggo.jpg')) {
    // ...
}
```

### Get file info
_Requires the `readFiles` capability_

```php
$file = $client->getFileInfo($file);
// or
$file = $client->getFileInfoById($fileId);
```

### Delete a file
_Requires the `deleteFiles` capability_

```php
$client->deleteFile($file);
```

If a file has governance restrictions, access keys with the `bypassGovernance` capability can force delete files.
```php
$client->deleteFile($file, true);
```

### Upload a file
_Requires the `writeFiles` capability_

Files can be uploaded by either passing a string content
```php
$fileContent = 'Hello world!';
// or
$fileContent = file_get_contents('hello.txt');
$client->upload($bucket, $fileContent, 'files/hello.txt');
```

or files can be passed in as a resource

```php
$file = fopen('smoldoggo.png', 'r');
$client->upload($bucket, $file, 'animals/dogs/smoldoggo.png');
```

**⚠ Resource content is loaded into memory before being sent. This is due to a limitation where the Symfony HttpClient
library sends headers _after_ the content, and the Backblaze API expects it before. As a result, we can't directly
pass the resource directly to Backblaze. This is currently a //TODO to find a workaround for. If you intend to upload
large files, this may be an issue.**

### Download a file
_Requires the `readFiles` capability_

Downloaded content is simply returned into a variable
```php
$content = $client->download($file);
echo $file;
// Hello world!
```

but can also be passed directly into a resource
```php
$resource = fwrite('/tmp/smoldoggo.png', 'w');
$client->download($file, $resource);
fclose($resource);
```

## Testing

[In terms of tests, there are no tests.](https://www.youtube.com/watch?v=3moREAbLl-I) _(Boooo)._

This library is still a work in progress as I don't yet fully support the B2 API, nor have I ironed out 100% of the
kinks (see uploading a resource for example).

Most of the functionality works (I use it in my own app), I guess you can call that some sort of guarantee?

But don't worry, once I've built out the library some more there will be tests - I promise!

## License

Made with :heart: by Javan Eskander

Available for use under the MIT license.