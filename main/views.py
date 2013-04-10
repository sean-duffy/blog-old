from main.models import Post
from django.http import HttpResponse, Http404
from django.template import Context, loader, RequestContext
from django.shortcuts import render_to_response, get_object_or_404, render, get_list_or_404
from django.utils import dateformat
from datetime import date
from markdown import markdown
from django.core.paginator import Paginator, EmptyPage

def index(request):
	return render_to_response('main/index.html', RequestContext(request))

def about(request):
	return render_to_response('main/about.html', RequestContext(request))

def projects(request):
	return render_to_response('main/projects.html', RequestContext(request))

def post(request, year, month, title):
	spaced_title = title.replace('-', ' ')
	post = get_object_or_404(Post, post_date__year=year, post_date__month=month, title__icontains=spaced_title)
	date_format = dateformat.DateFormat(post.post_date)
	date_text = date_format.format('jS \o\\f F\, Y')
	post_url = year + '/' + month + '/' + title
	return render(request, 'main/post.html', {
		'post_title' : post.title,
		'post_date' : date_text,
		'post_body' : markdown(post.body_text),
		'post_url' : post_url
		})

def blog(request, page):
	post_list = get_list_or_404(Post)[::-1]
	pages = Paginator(post_list, 3)

	if pages.page(page).has_next():
		next_page = int(page) + 1
	else:
		next_page = 0

	if pages.page(page).has_previous():
		previous_page = int(page) - 1
	else:
		previous_page = 0

	try:
		post_names = pages.page(page)
		posts = []
		for post_name in post_names:
			posts.append(get_object_or_404(Post, title=post_name))

		post_data = [[] for i in range(len(posts))]
		i = 0
		for post in posts:
			date_format = dateformat.DateFormat(post.post_date)
			post_date = post.post_date
			post_data[i].append(str(post_date.year) + '/' + str(post_date.month) + '/' + post.title.replace(' ', '-'))
			post_data[i].append(post.title)
			post_data[i].append(date_format.format('jS \o\\f F\, Y'))
			post_data[i].append(markdown(post.body_text))
			i += 1

		return render(request, 'main/blog.html', {
			'post_data' : post_data,
			'next_page': next_page,
			'previous_page' : previous_page,
			'current_page' : page,
			'num_pages' : pages.num_pages
			})

	except EmptyPage:
		raise Http404