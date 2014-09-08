Pretty simple plugin built on an original post by Smashing Magazine:
http://www.smashingmagazine.com/2012/01/27/limiting-visibility-posts-username/

The admin interface code (meta-box etc) is pretty much the same, although I wrapped it as a plugin rather than using functions.php (which seems like a bad idea even on a sunny day).

The redirecting part for unauthorized users could use some prettyfication - currently echoing a JS redirect with a HTML meta fallback, followed by dying.
