<?php

class IssuesController extends BaseController {


    public function index()
    {
        View::share('menu_item', 'issues');
        $data = array(
            'css' => array(),
            'js' => array(),
            'title' => trans('issues.issues'),
        );

        return View::make('cabinet.main', $data)
            ->nest('body', 'cabinet.dashboard.index', $data);
    }

    public function project($project_id, $stats='not_done')
    {
        View::share('menu_item', 'issues');
        $project = Projects::find($project_id);

        $params = array(
            'project_id' => $project_id,
            'statuses' => $this->_statsMapper($stats),
        );
        $issues = Issues::getInstance()->getProjectIssues($params);
        $data = array(
            'css' => array(),
            'js' => array(),
            'title' => $project->title,
            'project' => $project,
            'issues' => $issues,
        );

        return View::make('cabinet.main', $data)
            ->nest('body', 'cabinet.issues.project', $data);
    }

    public function view($issue_id)
    {
        View::share('menu_item', 'issues');

        $issue = Issues::find($issue_id);
        $commentsParams = array(
            'issue_id' => $issue_id,
            'user_id' => Auth::user()->id,
        );

        $data = array(
            'css' => array(),
            'js' => array(
                '/template/common/js/markupy.js',
                '/template/cabinet/js/issues/view.js',
            ),
            'title' => '#' . $issue->id . ' : ' . $issue->title,
            'issue' => $issue,
            'creator' => Users::getInstance()->getContact(Auth::user()->id, $issue->creator),
            'assigned' => Users::getInstance()->getContact(Auth::user()->id, $issue->assigned),
            'comments' => Comments::getInstance()->getComments($commentsParams),
            'files' => Files::getInstance()->getByIssue(array('issue_id' => $issue_id)),
            'contacts' => Users::getInstance()->getProjectContacts(Auth::user()->id, $issue->project_id),
        );

        return View::make('cabinet.main', $data)
            ->nest('body', 'cabinet.issues.view', $data);
    }

    public function newIssue($project_id)
    {
        View::share('menu_item', 'issues');

        $project = Projects::find($project_id);

        if(empty($project)) {
            return Redirect::to(URL::route('projects'));
        }

        $data = array(
            'css' => array(),
            'js' => array(
                '/template/common/js/markupy.js',
                '/template/cabinet/js/issues/new.js',
            ),
            'title' => trans('issues.new_issue'),
            'project' => $project,
            'contacts' => Users::getInstance()->getProjectContacts(Auth::user()->id, $project->id),
        );

        return View::make('cabinet.main', $data)
            ->nest('body', 'cabinet.issues.new', $data);
    }

    public function myIssues($stats='not_done')
    {
        View::share('menu_item', 'issues');
    }

    public function addComment($issue_id)
    {
        $comment = trim(Input::get('comment', ''));

        $userfiles = Input::file('userfile');

        if(Input::hasFile('userfile') || $comment != '') {
            $params = array(
                'creator' => Auth::user()->id,
                'comment' => Markupy::parse(e($comment)),
                'files_count' => (Input::hasFile('userfile')) ? count($userfiles) : 0,
                'issue_id' => intval($issue_id),
            );
            $commentId = Comments::getInstance()->addComment($params);
        } else {
            $commentId = null;
        }

        Issues::getInstance()->changeParams($issue_id, Input::all());

        if(Input::hasFile('userfile') && count($userfiles) <= 3) {
            $params = array(
                'file_object' => $userfiles,
                'issue_id' => $issue_id,
                'comment_id' => $commentId,
                'user_id' => Auth::user()->id,
            );
            Files::getInstance()->uploadCommentFiles($params);
        }

        return Redirect::to(URL::route('issue-view', array('issue_id' => $issue_id, '#comment' . $commentId)));
    }

    private function _statsMapper($stats)
    {
        $statsArray = array(
            'done' => array(
                'closed',
                'not_actual'
            ),
            'not_done' => array(
                'new',
                'opened',
                'in_work',
                'need_feedback',
            ),
            'new' => array('new'),
            'opened' => array('opened'),
            'in_work' => array('in_work'),
            'need_feedback' => array('need_feedback'),
            'closed' => array('closed'),
            'not_actual' => array('not_actual'),
        );

        return (isset($statsArray[$stats]) ? $statsArray[$stats] : 'not_done');
    }
}
