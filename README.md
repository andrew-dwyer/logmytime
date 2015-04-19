# logmytime
Command line app to log time to Assembla.

Very much a work in progress. Use at own risk. Pretty messy code too

## Usage

Interactively add time:
`php logmytime.php -i`

Basic ticket add:
`php logmytime.php -s SPACENAME -t TICKET_NUMBER -d DESCRIPTION -hr 1.5`

Set API key and API secret:
`php logmytime.php --apiKey API_KEY --apiSecret API_SECRET`
