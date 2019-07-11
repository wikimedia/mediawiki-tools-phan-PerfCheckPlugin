Performance Check Plugin
===============================

This is a phan plugin that looks for common micro-performance issues, while trying to be helpful
instead of annoying.

In fact, all of the suggested fixes:
* Don't affect readability, or improve it
* Provide a proved performance gain

For now, it has a single config setting, to be added to phan's `plugin_config` setting:
`perf_check_echo`. If true, issues will be echoed instead of emitted.
This is an awful hack only intended to help while auditing the plugin in Wikimedia CI.

### Requirements
* php >= 7.1.0
* Phan 2.1.0