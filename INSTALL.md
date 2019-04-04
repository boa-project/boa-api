 --------------------------------------
| Installation instructions |
 --------------------------------------

1 SYSTEM REQUIREMENTS

&nbsp;&nbsp;&nbsp;&nbsp;MySql 5.5 o superior  
&nbsp;&nbsp;&nbsp;&nbsp;PHP 7 or superior  
&nbsp;&nbsp;&nbsp;&nbsp;Additionally for php following components are required:  
&nbsp;&nbsp;&nbsp;&nbsp;PEAR MDB2 v 2.5.0b5 (pear install MDB2-2.5.0b5)  
&nbsp;&nbsp;&nbsp;&nbsp;PEAR MDB2 Driver mysqli v 1.5.0b4 (pear install MDB2\_Driver\_mysqli-1.5.0b4)  
&nbsp;&nbsp;&nbsp;&nbsp;PHP-CURL  


2 INSTALLING SOLR SEARCH Engine (Only if you are going to use Solr Search Engine)

Requires Java runtime environment (JRE) 1.8 or greater  
Requires PHP-SOLR extension (View https://www.php.net/manual/es/book.solr.php)  

For a full description of installation instructions visit 
    [https://lucene.apache.org/solr/guide/6_6/taking-solr-to-production.html]()

&nbsp;&nbsp;&nbsp;&nbsp;a. Directory Structure  

&nbsp;&nbsp;&nbsp;&nbsp;By default the installation script will use the following directory structure:

	/opt/solr-7.0.0
	/opt/solr -> /opt/solr-7.0.0 //The symbolik link is created so the installation scripts  
	and other commands do not depend on the solr version, also when upgrading only  
	updating the symbolic link will be required to point to the new installation.

It is recommended to have a separete directory for writable files, by default installation script will create:

    /var/solr

&nbsp;&nbsp;&nbsp;&nbsp;b. Create a Solr user  

&nbsp;&nbsp;&nbsp;&nbsp;it is not recommended to run solr service as root. By default the installation script will create a solr user, this value can be override by using the -u option when running the installation script.

c. Running solr installation script  

* Download solr installation package from: 
			[https://lucene.apache.org/solr/mirrors-solr-latest-redir.html]()
* Extract installation script on current directory  

  ```
  tar xzf solr-7.0.0.tgz solr-7.0.0/bin/install_solr_service.sh --strip-components=2
  //If installing on Red Hat, make sure you have lsof installed before (sudo yum install lsof)
  ```

* Execute the installation script

```
    sudo bash ./install_solr_service.sh solr-7.0.0.tgz
	//this command will install with all default options
	//to run a customized installation use:
	sudo bash ./install_solr_service.sh solr-7.0.0.tgz -i /opt -d /var/solr -u solr -s solr -p 8983 -n
    // -i: Target installation directory
    // -d: Target data directory
    // -u: Service identity user
    // -s: Service name
    // -p: Port to run the service
    // -n: Prevent service to start automatically after installation 
    //To see all installation options run:
    sudo bash ./install_solr_service.sh -help   
```

3 CREATING SOLR CORE FOR BoA AND START SERVICE

* Unzip solr\_boa\_core.zip package to the solr data directory (normally /var/solr). You can find solr_boa_core.zip in the tools folder of the boa-api project source, see step 4 on how to download boa-api project source code. After unzipped you should have a path like /var/solr/boa.
* Once package is unzipped, modify boa/core.properties to assign key dataDir with the path where solr should store indexed data for boa.
* Restart/start solr service
```
  service solr [restart|start]
```
* Navigate to [http://[yourserver]:8983/solr]() and you should see the solr console and boa under the Core Selector.

	
4 INSTALLING BOA API WEB SERVICE

a. Download or clone boa-api project source to a folder on the installation server 
 	(git clone https://github.com/boa-project/boa-api.git [installation-folder])

b. Create a database with UTF8 General CI encoding, this database will be used to store boa general system information.  

c. Create a user with read/write access to the above created database.

b. Locate [installation-folder]/src/properties.json.sample file and copy it to [installation-folder]/src/properties.json 

c. Edit [installation-folder]/src/properties.json to provide database access configuration and others:

<strong>URI\_BASE</strong>: Set this to the base url where the boa-api is installed. e.g http://localhost/boaapi  
<strong>URI\_BOA\_ADMIN\_API</strong>: If you are using Solr Search Engine, set this to the url where the boa admin project is installed plus "/api". This url will be consumed locally only so you can use localhost. e.g http://localhost/boa2/api  
<strong>URI\_SOLR\_SERVICE</strong>: Set this to the url where the solr service is installed. This url will be consumed locally only so you can use localhost. e.g http://localhost:8983/solr/[core]. Core should be set to the core name you create it in step 3. e.g boa 
<strong>LONG\_RANDOM\_SALT\_STRING</strong>: Set this to a random string value. it will be used as an additional input when hashing strings to the database. You can use [https://api.wordpress.org/secret-key/1.1/salt/]() to generate this value. Then use any of the keys generated by that service.  
<strong>DEFAULT\_DATABASE\_CONNECTION\_STRING</strong>:Connection string for the database you created on step 4.b e.g mysqli://dbuser:dbpassword@localhost/dbname?charset=utf8  

5 SCHEDULE CRON EXECUTION  

Cron.php must be scheduled to run as a system task. This cron is reponsible to keep docs indexed among others tasks. Cron schedule can be programmed with the next line, recommendation is to execute it each 5 minutes:
```
5 * * * * /usr/bin/php /[BOA-API-INSTALLATION-DIR]/src/cron.php >/dev/null
```



  
