phphoo5 

a yahoo-like link directory for PHP5 and now PHP 7 too

2018 AGM



This program is free software; you can redistribute it and/or modify it under the terms of the GNU General Public License as published by the Free Software Foundation; either version 2 of the License, or (at your option) any later version.  This program is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the GNU General Public License for more details.  You should have received a copy of the GNU General Public License along with this program; if not, write to the Free Software Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA  02111-1307, USA.

phphoo3:

Copyright (C) 1999/2001 Rolf V. Ostergaard http://www.cable-modems.org/phpHoo/ (https://web.archive.org/web/20150527052550/http://www.cable-modems.org:80/phpHoo/)

phphoo

Refer to http://www.webreference.com/perl/xhoo/php1/ (https://web.archive.org/web/20010207210145/http://www.webreference.com/perl/xhoo/php1/) for phphoo, the first cut done by CDI.



Setup:


Copy phphoo to your webroot and verify index.php is loaded when you navigate there.

Create your database with the schema file

> mysql -u username -p < sql.schema

grant access to a mysql user for phphoo 

> mysql -u username -p

> GRANT ALL PRIVILEGES ON phpHoo.* TO 'phphoouser'@'localhost'  identified by "phphoopass";

Rename config.php.dist to config.php and fill in your admin credentials and mysql connection settings. 

This includes MIT licensed mysql compatibility shim libraries from https://github.com/dshafik/php7-mysql-shim/ 
