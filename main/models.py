from django.db import models

class Post(models.Model):
	title = models.CharField(max_length=200)
	post_date = models.DateField('date posted')
	body_text = models.TextField()
	def __unicode__(self):
		return self.title