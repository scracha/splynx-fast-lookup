Takes IPv4 address and does a fast lookup of the active internet services using this address. It relies on a separate process that populate memory from the Splynx API that is intended to be ran on a schedule. Could be used for other fields that Splynx API doesn't natively handle very well.
-----------------------------

splynx_exporter_cli.php -
This is a Command Line Interface (CLI) script intended to run periodically (e.g., daily via a cron job) to compile the service data.

api.php -
This is the fast, low-latency API endpoint that the front-end tool calls. It is designed to be extremely fast because it avoids Splynx API calls.

index.html -
This is the client-side user interface for the lookup tool. It is a single-page HTML file using Tailwind CSS for styling and JavaScript for functionality.

config.php -
This file holds all the essential configuration settings for both the data exporter and the API endpoint.



Suggested crontab

# Run Splynx data exporter daily at 1 AM
0 1 * * * /usr/bin/php /var/www/html/splynx-service/splynx_exporter_cli.php >> /var/log/splynx_exporter.log 2>&1
