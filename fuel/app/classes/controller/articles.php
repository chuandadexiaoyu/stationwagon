<?php

class Controller_Articles extends Controller_Common {
	
	public function before()
	{
    	parent::before();
	}
	
	public function action_index($show = 'published')
	{
		if ($show === 'published')
		{
			$published = 1;
		}
		else
		{
			$published = 0;
		}
		
		$total_articles = Model_Article::find()
			->where('user_id', $this->user_id)
			->where('published', $published)
			->count();
        
		Pagination::set_config(array(
			'pagination_url' => 'articles/index/'.$show.'/',
			'per_page' => 5,
			'total_items' => $total_articles,
			'num_links' => 3,
			'uri_segment' => 4,
        ));

		$articles = Model_Article::find('all', array(
			'offset' => Pagination::$offset,
			'limit' => Pagination::$per_page,
			'include' => 'category',
			'where' => array(
				array('user_id', '=', $this->user_id),
				array('published', '=', $published),
			),
		));
        
        $this->template->title = 'Articles';
        $this->template->content = View::factory('articles/index')
			->set('total_articles', $total_articles)
			->set('articles', $articles, false)
			->set('show', $show);
    }
    
    public function action_add()
    {
        $val = Validation::factory('add_article');
        $val->add('category_id', 'Category');
        $val->add('title', 'Title')->add_rule('required');
        $val->add('body', 'Body')->add_rule('required');
        
        if ($val->run())
        {
            if (Input::post('save_draft'))
            {
                $status = 0;
            }
            else
            {
                $status = 1;
            }
            
			if (!$val->input('category_id'))
			{
				$category_id = null;
			}
			else
			{
				$category_id = $val->validated('category_id');
			}
			
            $article = new Model_Article(array(
				'user_id' => $this->user_id,
                'category_id' => $category_id,
                'title' => $val->validated('title'),
                'body' => $val->validated('body'),
                'created_time' => Date::factory()->get_timestamp(),
                'published' => $status,
            ));

            if ($article->save())
			{
				Session::set_flash('success', 'Article successfully added.');
			}
			else
			{
				Session::set_flash('error', 'Something went wrong, please try again!');
			}
            
            Response::redirect('articles/add');
        }
        
        $this->template->title = 'Add Article';
        $this->template->content = View::factory('articles/add')
			->set('categories', Model_Category::find('all'), false)
			->set('val', Validation::instance('add_article'), false);
    }
    
    public function action_edit($id)
    {
        $article = Model_Article::find_by_id_and_user_id($id, $this->user_id);
        
       	$val = Validation::factory('edit_article');
        $val->add('category_id');
        $val->add('title')->add_rule('required');
        $val->add('body')->add_rule('required');
        
        if ($val->run())
        {
			if (!$val->input('category_id'))
			{
				$category_id = null;
			}
			else
			{
				$category_id = $val->validated('category_id');
			}
			
            $article->category_id = $category_id;
            $article->title = $val->validated('title');
            $article->body = $val->validated('body');
            
			if ($article->save())
			{
				Session::set_flash('success', 'Article successfully updated.');
			}
			else
			{
				Session::set_flash('error', 'Something went wrong, please try again!');
			}

            Response::redirect('articles/edit/'.$article->id);
        }
        
        $this->template->title = 'Edit Article - '.$article->title;
        $this->template->content = View::factory('articles/edit')
			->set('categories', Model_Category::find('all'), false)
			->set('article', $article, false)
			->set('val', Validation::instance('edit_article'), false);
    }
    
    public function action_publish($id)
    {
        $article = Model_Article::find_by_id_and_user_id($id, $this->user_id);
        $article->published = 1;
        $article->save();

        Response::redirect('articles');
    }
    
    public function action_delete($id)
    {
        Model_Article::find_by_id_and_user_id($id, $this->user_id)->delete();
        
        Response::redirect('articles');
    }
}