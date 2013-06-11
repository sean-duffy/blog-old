from django.conf.urls import patterns, include, url

from django.contrib import admin
admin.autodiscover()

urlpatterns = patterns('',
    url(r'^$', 'main.views.index'),
    url(r'^blog/$', 'main.views.blog', {'page': 1}),
    url(r'^blog/page/(?P<page>\d+)/$', 'main.views.blog'),
    url(r'^blog/(?P<year>\d+)/(?P<month>\d+)/(?P<title>\S+)/$', 'main.views.post'),
    url(r'^about/$', 'main.views.about'),
    url(r'^projects/$', 'main.views.projects'),
    url(r'^archive/$', 'main.views.archive'),
    url(r'^cv/$', 'main.views.cv'),
    url(r'^admin/', include(admin.site.urls)),
)