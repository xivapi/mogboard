# MogBoard.com

Source code for MogBoard.com

This is a fully featured, enhanced PHP version of: https://github.com/viion/marketboard

## Local Development

To run this site locally, you need Vagrant: https://www.vagrantup.com/. If you use docker and are on windows this will clash... I am working on getting a docker setup for this.

You will also need the Vagrant: Host Manager plugin:
- `vagrant plugin install vagrant-hostmanager`

**Getting an environment running:**
- Open up the repository in command
- `cd path/to/repo/vm`
- `vagrant up`

This will bring the vagrant up and boot the site on: http://mogboard.local/

It will also setup:
- http://staging.mogboard.local/
- http://mogboard.adminer

The *adminer* url is for viewing the database via the web, easier for development.

Once all is running you can ssh into the machine via:
- `vagrant ssh`
- `cd /vagrant` The VM directory /vagrant = path/to/repo/

Assets such as JS and SCSS use Encore Webpack, install Encore via:
- `yarn` or `npm i`
- You may also need sass `yarn add sass-loader@^7.0.1 node-sass --dev`

You can then run a watcher (outside of vagrant) via:
- `bash bin/webpack` - Watches the `/assets/js/*` and `/assets/scss/*` files
- `bash bin/webpack_prod` - Builds production ready javascript


## VM

Local development has a Vagrant VM and uses:

- Nginx
- PHP 7.2
- MySQL
- Redis
