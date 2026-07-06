# ra_delivery

Standalone delivery-integration workspace.

This is a simple package to monitor and report on emails that are sent but could not be delivered. There are three parts:
  1. A batch plug-in that interrogates an SMTP server and logs delivery exceptions to a database table. This is invoked from the command line with command 'ra_delivery:pollactivity"
  2. An on-line component that allows viewing of the database records, and allows configuration of the plug-in.
  3. A wrapper to the STMT2GO API for sending emails. A simple test in com_ra_tools/ToolsHelper/sndEmail checks if component com_ra_delivery is installed and enabled. If so, this wrapper is invoked, and the email is sent straight to the API without using Joomla's mailer.

The initial version of the plugin is specific to the API provided by SMTP2GO, but it would be straightforward to customise or clone the interface for a different provider.


Folder structure:

- `com_ra_delivery` Joomla component
- `plg_ra_delivery` Joomla console plug-in

Design points to note:

- Poll SMTP2GO `/activity/search`
- Filter by configured event types
- Persist extract watermark in `#__ra_control` using `record_type = 2`
- Store raw and normalised event rows locally
- Run polling from a Joomla CLI command
