@extends('home.layout')

@section('home-title') {{ $submission->prompt_id ? 'Submission' : 'Claim' }} (#{{ $submission->id }}) @endsection

@section('home-content')
@if($submission->prompt_id)
    {!! breadcrumbs(['Admin Panel' => 'admin', 'Prompt Queue' => 'admin/submissions/pending', 'Submission (#' . $submission->id . ')' => $submission->viewUrl]) !!}
@else 
    {!! breadcrumbs(['Admin Panel' => 'admin', 'Claim Queue' => 'admin/claims/pending', 'Claim (#' . $submission->id . ')' => $submission->viewUrl]) !!}
@endif

@if($submission->status == 'Pending')

    <h1>
        {{ $submission->prompt_id ? 'Submission' : 'Claim' }} (#{{ $submission->id }})
        <span class="float-right badge badge-{{ $submission->status == 'Pending' ? 'secondary' : ($submission->status == 'Approved' ? 'success' : 'danger') }}">{{ $submission->status }}</span>
    </h1>

    <div class="mb-1">
        <div class="row">
            <div class="col-md-2 col-4"><h5>User</h5></div>
            <div class="col-md-10 col-8">{!! $submission->user->displayName !!}</div>
        </div>
        @if($submission->prompt_id)
            <div class="row">
                <div class="col-md-2 col-4"><h5>Prompt</h5></div>
                <div class="col-md-10 col-8">{!! $submission->prompt->displayName !!}</div>
            </div>
            <div class="row">
                <div class="col-md-2 col-4"><h5>Previous Submissions</h5></div>
                <div class="col-md-10 col-8">{{ $count }} {!! add_help('This is the number of times the user has submitted this prompt before and had their submission approved.') !!}</div>
            </div>
        @endif
        <div class="row">
            <div class="col-md-2 col-4"><h5>URL</h5></div>
            <div class="col-md-10 col-8"><a href="{{ $submission->url }}">{{ $submission->url }}</a></div>
        </div>
        <div class="row">
            <div class="col-md-2 col-4"><h5>Submitted</h5></div>
            <div class="col-md-10 col-8">{!! format_date($submission->created_at) !!} ({{ $submission->created_at->diffForHumans() }})</div>
        </div>
    </div>
    <h2>Comments</h2>
    <div class="card mb-3"><div class="card-body">{!! nl2br(htmlentities($submission->comments)) !!}</div></div>
    @if(Auth::check() && $submission->staff_comments && ($submission->user_id == Auth::user()->id || Auth::user()->hasPower('manage_submissions')))
        <h5 class="text-danger">Staff Comments ({!! $submission->staff->displayName !!})</h5>
        <div class="card border-danger mb-3"><div class="card-body">{!! nl2br(htmlentities($submission->staff_comments)) !!}</div></div>
    @endif

    {!! Form::open(['url' => url()->current(), 'id' => 'submissionForm']) !!}

        <h2>Rewards</h2>
        @include('widgets._loot_select', ['loots' => $submission->rewards, 'showLootTables' => true])
        @if($submission->prompt_id)
            <div class="mb-3">
                @include('home._prompt', ['prompt' => $submission->prompt, 'staffView' => true])
            </div>
        @endif

        <h2>Characters</h2>
        <div id="characters" class="mb-3">
            @foreach($submission->characters as $character)
                @include('widgets._character_select_entry', ['characterCurrencies' => $characterCurrencies, 'character' => $character])
            @endforeach
        </div>
        <div class="text-right mb-3">
            <a href="#" class="btn btn-outline-info" id="addCharacter">Add Character</a>
        </div>
		<div class="form-group">
            {!! Form::label('staff_comments', 'Staff Comments') !!}
            {!! Form::textarea('', null, ['class' => 'form-control', 'id' =>  'modalStaffComments']) !!}
        </div>
        {!! Form::hidden('staff_comments', null, ['id' => 'staffComments']) !!}
        <div class="text-right">
            <a href="#" class="btn btn-danger mr-2" id="rejectionButton">Reject</a>
            <a href="#" class="btn btn-success" id="approvalButton">Approve</a>
        </div>

    {!! Form::close() !!}

    <div id="characterComponents" class="hide">
        <div class="submission-character mb-3 card">
            <div class="card-body">
                <div class="text-right"><a href="#" class="remove-character text-muted"><i class="fas fa-times"></i></a></div>
                <div class="row">
                    <div class="col-md-2 align-items-stretch d-flex">
                        <div class="d-flex text-center align-items-center">
                            <div class="character-image-blank">Enter character code.</div>
                            <div class="character-image-loaded hide"></div>
                        </div>
                    </div>
                    <div class="col-md-10">
                        <a href="#" class="float-right fas fa-close"></a>
                        <div class="form-group">
                            {!! Form::label('slug[]', 'Character Code') !!}
                            {!! Form::text('slug[]', null, ['class' => 'form-control character-code']) !!}
                        </div>
                        <div class="character-rewards hide">
                            <h4>Character Rewards</h4>
                            <table class="table table-sm">
                                <thead>
                                    <tr>
                                        <th width="70%">Reward</th>
                                        <th width="30%">Amount</th>
                                    </tr>
                                </thead>
                                <tbody class="character-rewards">
                                </tbody>
                            </table>
                            <div class="text-right">
                                <a href="#" class="btn btn-outline-primary btn-sm add-reward">Add Reward</a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <table>
            <tr class="character-reward-row">
                <td>
                    {!! Form::select('character_currency_id[]', $characterCurrencies, 0, ['class' => 'form-control currency-id']) !!}
                </td>
                <td class="d-flex align-items-center">
                    {!! Form::text('character_quantity[]', 0, ['class' => 'form-control mr-2 quantity']) !!}
                    <a href="#" class="remove-reward d-block"><i class="fas fa-times text-muted"></i></a>
                </td>
            </tr>
        </table>
    </div>
    @include('widgets._loot_select_row', ['items' => $items, 'currencies' => $currencies, 'showLootTables' => false])

    <div class="modal fade" id="confirmationModal" tabindex="-1" role="dialog">
        <div class="modal-dialog" role="document">
            <div class="modal-content hide" id="approvalContent">
                <div class="modal-header">
                    <span class="modal-title h5 mb-0">Confirm Approval</span>
                    <button type="button" class="close" data-dismiss="modal">&times;</button>
                </div>
                <div class="modal-body">
                    <p>This will approve the {{ $submission->prompt_id ? 'submission' : 'claim' }} and distribute the above rewards to the user.</p>
                    <div class="text-right">
                        <a href="#" id="approvalSubmit" class="btn btn-success">Approve</a>
                    </div>
                </div>
            </div>
            <div class="modal-content hide" id="rejectionContent">
                <div class="modal-header">
                    <span class="modal-title h5 mb-0">Confirm Rejection</span>
                    <button type="button" class="close" data-dismiss="modal">&times;</button>
                </div>
                <div class="modal-body">
                    <p>This will reject the {{ $submission->prompt_id ? 'submission' : 'claim' }}.</p>
                    <div class="text-right">
                        <a href="#" id="rejectionSubmit" class="btn btn-danger">Reject</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
@else
    <div class="alert alert-danger">This {{ $submission->prompt_id ? 'submission' : 'claim' }} has already been processed.</div>
    @include('home._submission_content', ['submission' => $submission])
@endif

@endsection

@section('scripts')
@parent 
@if($submission->status == 'Pending')
    @include('js._loot_js', ['showLootTables' => true])
    @include('js._character_select_js')

    <script>
        
        $(document).ready(function() {
            var $confirmationModal = $('#confirmationModal');
            var $submissionForm = $('#submissionForm');
            var $staffComments = $('#staffComments');
            var $modalStaffComments = $('#modalStaffComments');

            var $approvalButton = $('#approvalButton');
            var $approvalContent = $('#approvalContent');
            var $approvalSubmit = $('#approvalSubmit');

            var $rejectionButton = $('#rejectionButton');
            var $rejectionContent = $('#rejectionContent');
            var $rejectionSubmit = $('#rejectionSubmit');
            
            $approvalButton.on('click', function(e) {
                e.preventDefault();
                $approvalContent.removeClass('hide');
                $rejectionContent.addClass('hide');
                $confirmationModal.modal('show');
            });
            
            $rejectionButton.on('click', function(e) {
                e.preventDefault();
                $rejectionContent.removeClass('hide');
                $approvalContent.addClass('hide');
                $confirmationModal.modal('show');
            });

            $approvalSubmit.on('click', function(e) {
                e.preventDefault();
                $staffComments.val($modalStaffComments.val());
                $submissionForm.attr('action', '{{ url()->current() }}/approve');
                $submissionForm.submit();
            });

            $rejectionSubmit.on('click', function(e) {
                e.preventDefault();
                $staffComments.val($modalStaffComments.val());
                $submissionForm.attr('action', '{{ url()->current() }}/reject');
                $submissionForm.submit();
            });
        });

    </script>
@endif
@endsection