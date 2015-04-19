# logmytime
Command line app to log time to Assembla.

Very much a work in progress. Use at own risk. Pretty messy code too

## Installation 

`wget https://github.com/andrew-dwyer/logmytime/releases/download/0.1/logmytime.phar`

## Usage

Interactively add time:
`logmytime.phar -i`

Basic ticket add:
`logmytime.phar -s SPACENAME -t TICKET_NUMBER -d DESCRIPTION -hr 1.5`

Set API key and API secret:
`logmytime.phar --apiKey API_KEY --apiSecret API_SECRET`
