# yii2-zendesk
Yii2 plugin for zendesk service support (https://www.zendesk.com)

## How to use:
Add the following line to composer.json, __require__ section
```json
"require": {
    "mirocow/yii2-zendesk": "dev-master",
} 
```

Add a component to your main.php config file
```php
'components' =>
    'zendesk' => [
        'class' => 'mirocow\zendesk\Client',
        'apiKey' => 'YOUR_API_KEY',
        'user' => 'YOUR_USER',
        'baseUrl' => 'https://SUBDOMAIN.zendesk.com/api/v2',
        'password' => 'YOUR_PASSWORD',
        'authType' => 'basic'
    ]
]
```

The most simple example is:
Create an instance of Zendesk Client
```php
$client = new mirocow\zendesk\Client();
```

Execute
```php
$results = $client->execute('GET', '/users.json', []);
```
OR
```php
$results = $client->get('/users.json', []);
```

Another variant is to use build-in plugin functions to work with Users, Tickets, Search, Attachments instances.
In your form handler use:
```php
use yii\helpers\StringHelper;
use mirocow\zendesk\Attachment;
use mirocow\zendesk\Search;
use mirocow\zendesk\Ticket;
use mirocow\zendesk\User;
use Yii;
use yii\web\UploadedFile;
```

If you wants to use upload, you should use a well-known ```\yii\web\UploadedFile``` instanse
```php
$uploadedFile = new UploadedFile(['tempName' => 'YOUR_FILE_TEMPNAME', 'name' => 'YOUR_FILE_NAME]);
```

Then - create and save zendesk Attachment instance from UploadedFile
```php
$zAttachment = new Attachment(['uploadedFile' => $uploadedFile]);
$token = $zAttachment->save();
```

You can also use zendesk search API for existing Users of your zendesk account
If there are no such users - let's create it
```php
$search = new Search(['query' => ['email' => 'user@example.com']]);
if ($zUsers = $search->users()) {
    $zUser = $zUsers[0];
} else {
    $zUser = new User(['email' => 'user@example.com']);
    $zUser->save();
}
```

You can also use zendesk search API for existing Ticket
```php
$items = (new Search([
  'query' => [
    'status' => Ticket::STATUS_OPEN,
    'requester' => Yii::$app->user->identity->email
  ]
]))->tickets();
```
OR
```php
$zSearch = new Search([
  'query' => [
    'status' => Ticket::STATUS_OPEN,
    'requester' => Yii::$app->user->identity->email
  ]
]);

$zSearch->sort_by = Ticket::SORT_BY_CREATED;
$zSearch->sort_order = Search::SORT_ASC;
$items = $zSearch->tickets();
```

And finally, it's time to create a Ticket instance
```php
$zTicket = new Ticket([
    'requester_id' => $zUser->id,
    'requester' => [
        'email' => $zUser->email
    ],
    'subject' => StringHelper::truncate('Problem: Authorization', 100),
    'comment' => [
        'body' => 'Authorization not works!'
    ],
]);
```

If we had an uploads - attach them to a ticket comment field
```php
$zTicket->comment['uploads'] = isset($token) && $token ? [$token] : null;
$zTicket->save();
```

Thats all.
