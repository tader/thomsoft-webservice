DESTDIR =

TARGETDIR = $(DESTDIR)/usr/share/php/libthomsoft-webservice-php/
DOCPATH   = $(DESTDIR)/usr/share/doc/libthomsoft-webservice-php/
BINDIR	  = $(DESTDIR)/usr/bin/

TOPFILES  = README.txt 

install-framework:
	install -d -o www-data -m 755 $(TARGETDIR)/

	cp -r src/*.php $(TARGETDIR)/
	chown -Rf www-data:www-data $(TARGETDIR)/
	find $(TARGETDIR)/ -type f -exec chmod 644 {} \;

	install -d -m 755 $(DOCPATH)/
	install -m 644 $(TOPFILES) $(DOCPATH)/
	cp -r demos $(DOCPATH)/

