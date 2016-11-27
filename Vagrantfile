# -*- mode: ruby -*-
# vi: set ft=ruby :

BEGIN {
    required_plugins = %w( vagrant-vbguest )
    required_plugins.each do |plugin|
        puts "You should install the #{plugin} with 'vagrant plugin install #{plugin}''" unless Vagrant.has_plugin? plugin
    end
}

Vagrant.configure(2) do |config|
    config.vm.box = "centos/7"

    # Disable default sync folders
    config.vm.synced_folder ".", "/vagrant", disabled: true
    config.vm.synced_folder ".", "/home/vagrant/sync", disabled: true

    # Enable specific folders, it helps if you have installed the vagrant-vbguests plugin :)
    config.vm.synced_folder ".", "/vagrant", type: "virtualbox", mount_options: ["dmode=777", "fmode=666"]
    config.vm.synced_folder "vendor/", "/vagrant/vendor/", type: "virtualbox", mount_options: ["dmode=755", "fmode=644"]
    config.vm.synced_folder "telegramApiLogs", "/vagrant/vendor/telegramApiLogs", type: "virtualbox", mount_options: ["dmode=755", "fmode=666"]

    # For slow host machines, like mine :(
    config.vm.boot_timeout = 500

    # Create network: one private in order to always be able to reach that machine with the same ip
    config.vm.network "private_network", ip: "192.168.33.15"

    # Copy all scripts that will finish the installation
    config.vm.provision "file", source: "VagrantProvisionScripts/php-fpm-telegram.conf", destination: "/home/vagrant/php-fpm-telegram.conf"
    config.vm.provision "file", source: "VagrantProvisionScripts/nginx-telegram.conf", destination: "/home/vagrant/nginx-telegram.conf"
    config.vm.provision "file", source: "VagrantProvisionScripts/phpfpm-access-to-shared-folder.te", destination: "/home/vagrant/phpfpm-access-to-shared-folder.te"
    config.vm.provision "file", source: "VagrantProvisionScripts/phpfpm-access-to-write-logs.te", destination: "/home/vagrant/phpfpm-access-to-write-logs.te"
    config.vm.provision "file", source: "VagrantProvisionScripts/userrights.sql", destination: "/home/vagrant/userrights.sql"
    # Invoke the installation itself
    config.vm.provision :shell, path: "VagrantProvisionScripts/base.sh"
end
