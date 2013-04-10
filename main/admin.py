from django.contrib import admin
from main.models import Post

class PostAdmin(admin.ModelAdmin):
	list_display = ('title', 'post_date')

admin.site.register(Post, PostAdmin)