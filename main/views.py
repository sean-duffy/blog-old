from main.models import Post
from django.http import HttpResponse, Http404, QueryDict
from django.template import Context, loader, RequestContext
from django.shortcuts import render_to_response, get_object_or_404, render, get_list_or_404
from django.utils import dateformat
from datetime import date
import markdown
from django.core.paginator import Paginator, EmptyPage
import re

class MyExtension(markdown.extensions.Extension):
    def extendMarkdown(self, md, md_globals):
        md.preprocessors.add('ImageProcessor', ImageFormatter(md.preprocessors), '_end')

class ImageFormatter(markdown.preprocessors.Preprocessor):
    def run(self, lines):
        new_lines = []
        for line in lines:
            match = re.match('^!\[(.*)\]\((.*)"(.*)"\)$', line)
            if match:
                alt_text = match.group(1)
                image_url = match.group(2)
                caption_text = match.group(3)

                if 'flickr' in image_url:
                    image_link_url = image_url.replace('_c.jpg', '_b.jpg')
                else:
                    image_link_url = image_url

                line = '<a href="' + image_link_url + '" target="_blank">'
                line += '<img src="' + image_url + '" alt="' + alt_text + '"></a>'
                line += '<div id="caption">' + caption_text + '</div>'
            new_lines.append(line)
        return new_lines

def index(request):
    return render_to_response('main/index.html', RequestContext(request))

def about(request):
    return render_to_response('main/about.html', RequestContext(request))

def cv(request):
    return render_to_response('main/cv.html', RequestContext(request))

def get_tagged_posts(tag=None):
    months = ['January', 'February', 'March', 'April', 'May', 'June', 'July',
    'August', 'September','October', 'November', 'December']
    years = [2013, 2014, 2015] # Add the year to this list each time a year passes

    all_posts = []

    for year in years:
        year_posts = []

        for month in range(12):
            if tag:
                posts = Post.objects.filter(post_date__month=month+1, post_date__year=year, title__contains=tag).order_by('post_date')
            else:
                posts = Post.objects.filter(post_date__month=month+1, post_date__year=year).order_by('post_date')
            if len(posts) > 0:
                year_posts.append([months[month], posts])

        for month in range(len(year_posts)):
            for post_num in range(len(year_posts[month][1])):
                date_format = dateformat.DateFormat(year_posts[month][1][post_num].post_date)
                year_posts[month][1][post_num].formatted_date = date_format.format('jS \o\\f F\, Y')
                year_posts[month][1][post_num].formatted_title = year_posts[month][1][post_num].title.replace(' ', '-')
        for month in range(len(year_posts)):
            year_posts[month][1] = year_posts[month][1][::-1]

        year_posts = year_posts[::-1]
        if len(year_posts) > 0:
            all_posts.append((year, year_posts))

    all_posts = all_posts[::-1]
    return all_posts

def projects(request):
    all_posts = get_tagged_posts('Project')

    return render(request, 'main/projects.html', {
        'all_posts': all_posts
        })

def post(request, year, month, title):
    spaced_title = title.replace('-', ' ').replace('_', ':')
    post = get_object_or_404(Post, post_date__year=year, post_date__month=month, title=spaced_title)
    date_format = dateformat.DateFormat(post.post_date)
    date_text = date_format.format('jS \o\\f F\, Y')
    post_url = year + '/' + month + '/' + title
    return render(request, 'main/post.html', {
        'post_title' : post.title,
        'post_date' : date_text,
        'post_body' : markdown.markdown(post.body_text, [MyExtension()]),
        'post_url' : post_url
        })

def blog(request, page):
    post_list = get_list_or_404(Post.objects.order_by('post_date'))[::-1]
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
            post_data[i].append(str(post_date.year) + '/' + str(post_date.month) + '/' + post.title.replace(' ', '-').replace(':', '_'))
            post_data[i].append(post.title)
            post_data[i].append(date_format.format('jS \o\\f F\, Y'))
            post_data[i].append(markdown.markdown(post.body_text, [MyExtension()]))
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

def archive(request):
    all_posts = get_tagged_posts()

    return render(request, 'main/archive.html', {
        'all_posts' : all_posts
        })
