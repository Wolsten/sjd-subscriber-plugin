# SJD Subscribe Plugin

This plugin supports capturing subscribers and issuing notifications to subscribers. It uses opt in confirmation and SMTP mail settings to improve deliverability. 

## Installation

To install, copy the required source files (not including the test.sh script) into it's own plugin folder `sjd_subscribe_plugin` 
and then Activate as normal.

## Usage

Create a page for managing subscriptions which has the `[sjd_subscribe_form]`.

## Settings

A Subscriber Settings page is provided and accessible from the main Dashboard menu.

When first setting up a website, set the `message delay` and `emails per block` to conservative values to reduce the frequency of emails sending. This is particularly important if you have a large number of subscribers as you don't want to have yor email marked as spam. As time goes on the delay can be reduce and the block size increased.




