@extends('layouts.master')
@section('heading')
    {{__('All Leads')}}
@stop

@section('content')
    <table class="table table-hover" id="leads-table">
        <thead>
        <tr>
            <th>{{ __('Title') }}</th>
            <th>{{ __('Deadline') }}</th>
            <th>{{ __('Created at') }}</th>
            <th>{{ __('Assigned') }}</th>
            <th>
                <select name="status_id" id="status-lead" class="table-status-input">
                    <option value="" disabled>{{ __('Status') }}</option>
                    @foreach($statuses as $status)
                        <option class="table-status-input-option" {{ $status->title == 'Open' ? 'selected' : ''}} value="{{$status->title}}">{{$status->title}}</option>
                    @endforeach
                    <option value="all">All</option>
                </select>
            </th>
        </tr>
        </thead>
    </table>

    <!-- DELETE MODAL SECTION -->
    <div id="deletion" class="modal fade" role="dialog">
        <div class="modal-dialog ">
        <!-- Modal content-->
        <div class="modal-content">
        <form method="POST" id="deletion-form">
            <div class="modal-header">
            <button type="button" class="close" data-dismiss="modal">&times;</button>
            <h4 class="modal-title">@lang('Deletion of ') <span id="deletion-title"></span></h4>
            </div>
            <div class="modal-body">
            @method('delete')
            @csrf
            <label style="font-weight: 300; color:#333; font-size:14px;">
                <input type="checkbox" name="delete_offers"> @lang('Delete offers') 
            </label>
            <p>
            @lang('If the offers are not deleted they will be attached to the client, without a reference to the lead').
            </p>
            <p>
            @lang('Keep in mind, every document, activity, appointment, and comment related will be deleted as well').
            </p>
            <p>
            @lang('Once deleted, it is not possible to restore it. Are you sure?')
            </p>
            </div>
            <div class="modal-footer">
            <button type="button" class="btn btn-default" data-dismiss="modal">@lang('Cancel')</button>
            <input type="submit" class="btn btn-brand" value="{{__('Delete')}}">
            </div>
            </form>
        </div>
    </div>
</div>
<!-- END OF THE DELETE MODAL SECTION -->
@stop

@push('scripts')
<style type="text/css">
    .table > tbody > tr > td {
        border-top:none !important;
    }
    .table-actions {
       opacity: 0;
    }
    #leads-table tbody tr:hover .table-actions{
      opacity: 1;
    }
    .title-table-tab {
        width:260px;
    }
</style>
    <script>
        $(function () {
            var table = $('#leads-table').DataTable({
                processing: true,
                serverSide: true,
                autoWidth: false,
                ajax: '{!! route('leads.data') !!}',
                language: {
                    url: '{{ asset('lang/' . (in_array(\Lang::locale(), ['dk', 'en']) ? \Lang::locale() : 'en') . '/datatable.json') }}'
                },
                drawCallback: function(){
                    var length_select = $(".dataTables_length");
                    var select = $(".dataTables_length").find("select");
                    select.addClass("tablet__select");
                },
                columns: [
                    {data: 'titlelink', name: 'title', class: 'title-table-tab'},
                    {data: 'deadline', name: 'deadline'},
                    {data: 'created_at', name: 'created_at'},
                    {data: 'user_assigned_id', name: 'user_assigned_id'},
                    {data: 'status_id', name: 'status.title', orderable: false},
                    {data: 'view', name: 'view', orderable: false, searchable: false, class: 'table-actions'},
                ]
            });
            table.columns(4).search('^' + 'Open' + '$', true, false).draw();
            $('#status-lead').change(function () {
                selected = $("#status-lead option:selected").val();
                if (selected == "all") {
                    table.columns(4).search('').draw();
                } else {
                    table.columns(4).search(selected ? '^' + selected + '$' : '', true, false).draw();
                }
            });
        });
        
        $( '#deletion' ).on( 'show.bs.modal', function (e) {
            var target = e.relatedTarget;
            var id = $(target).data('id');
            var title = $(target).data('title');
      
            $("#deletion-title").text(title);
            $('#deletion-form').attr('action', id)
        });
    </script>
@endpush

