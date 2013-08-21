# Autom8-Reportit

Automate add/remove data sources on reports

## Purpose

This plugin allows to create rules similar to Autom8 Graph - and Tree Rules to specify which data items need to be added to or removed from a report.

## Features

* Define Rules based on existing Data Queries
* Preview of matching Hosts and Data Sources
* Choose matching DS to overwrite or merge current list of DS on a report
* Rules are executed just before report calculation

## Prerequisites

This plugin is tested with Cacti 0.8.8a and PIA 3.1 but it should also work on older versions. The plugin contains patches for Autom8 v0.35 and Reportit v0.7.5a, older versions of these plugins will need different patches and might not work with this plugin.

## Installation

1. Untar contents to the Cacti plugins directory
2. Apply following [patches](#patches)
3. Install and Activate from Cacti UI

### Patches

In order to get this plugin running, applying patches to the plugins Autom8 and Reportit is required.

Run following commands from plugins directory:

```shell
patch --dry-run -N -d autom8/ -i autom8reportit/autom8_v035.patch
patch --dry-run -d reportit/ -i autom8reportit/reportit_v075a.patch
```

If everything looks ok, you can omit the `--dry-run` option and run the commands again to actually do the patches. 
**Note:** The patch autom8_v035.patch might warn you about reverting patches if you have done this patch already for the plugin [Autom8-Thold](https://github.com/Super-Visions/cacti-plugin-autom8thold/). In this case, you can ignore this patch as it is alread done, you still need to run the patch for Reportit plugin.
