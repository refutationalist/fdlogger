PROJNAME != ./pharbuild projname

$(PROJNAME).phar:
	./pharbuild


.PHONY: clean
clean:
	rm -f $(PROJNAME).phar


.PHONY: install_support
install_support:
	# extra config files
	mkdir -p $(DESTDIR)/usr/share/$(PROJNAME)
	install -m644 extra/lighttpd.conf-example $(DESTDIR)/usr/share/$(PROJNAME)


install: install_support $(PROJNAME).phar
	mkdir -p $(DESTDIR)/usr/lib $(DESTDIR)/usr/bin
	install -m644 $(PROJNAME).phar $(DESTDIR)/usr/lib
	install -m755 extra/bin.$(PROJNAME) $(DESTDIR)/usr/bin/$(PROJNAME)

.PHONY: dev
dev: clean $(PROJNAME).phar
	echo "If this doesn't work, create a test.ini with a proper database."
	LOGGERINI=$(shell pwd)/test.ini php -S 0.0.0.0:8000 $(PROJNAME).phar


.PHONY: version
# this is the simplest way to do it.
# consider better ways, including git.
version:
	@cat src/VERSION
