# SilverStripe-Social-Proof
=======
# SilverStripe Social Statistics module


## Maintainer Contact

Kirk Mayo

<kirk (at) solnet (dot) co (dot) nz>

## Requirements

* SilverStripe 3.2
* Access to create cron jobs
* https://github.com/silverstripe-australia/silverstripe-queuedjobs
* https://github.com/kmayo-ss/twitter-stripe

## Documentation

The module is used query social statistics for different web pages and store the information
and provide this information via an API

## Setup

The Twitter service makes use of another SilverStripe module which uses Oauth to use the Twitter API.
You will need to consult the the Readme file (../twitter-stripe/README.md) for details on setting up the Oauth details.
The module config.yml also contains a setting under SocialProofSetting which is used to lock the
urls down to certain domains.

## Services

The module queuries various social media services which are managed via config.yml
To add another service you will need to create a class with the properties service and statistics
as these are used when adding a row to the URLStatistics model.
The class will also need to declare a method called processQueue which is used in a cron job,
this is enforced by a interface called SocialServiceInterface which should be implemented in any
future service classes.

## API Endpoints

Curently the following API endpoints exist

```
http://socialproof.stripetheweb.com/api/countsfor?urls=http://[urltobeprocessed]
http://socialproof.stripetheweb.com/api/countsfor/service/facebook?urls=[urltobeprocessed]
http://socialproof.stripetheweb.com/api/countsfor/service/twitter?urls=[urltobeprocessed]
http://socialproof.stripetheweb.com/api/countsfor/service/linkedin?urls=[urltobeprocessed]
http://socialproof.stripetheweb.com/api/countsfor/service/linkedin?urls=[urltobeprocessed]
http://socialproof.stripetheweb.com/api/countsfor/service/google?urls=[urltobeprocessed]
```


## Composer Installation

  composer require solnet/socialproof

## TODO

Testing
