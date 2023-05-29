@extends('convert.main')

@section('content')
    @if($columns)
        <div class="form-check">
            <input class="form-check-input" type="checkbox" name="columns" id="all" value="all">
            <label class="form-check-label" for="all">
                Check all
            </label>
        </div>
        @foreach($columns as $key => $column)
            <div class="form-check">
                <input class="form-check-input"
                       type="checkbox"
                       name="{!! $column !!}"
                       id="{!! $key !!}"
                       value="{!! $column !!}"
                >
                <label class="form-check-label" for="{!! $key !!}">
                    {!! $column !!}
                </label>
            </div>
        @endforeach
    @else
        <form class="row g-3" method="post" action="{{ route('convertPost') }}" enctype="multipart/form-data">
        @csrf
        <div>
            <label for="formFileLg" class="form-label">Upload excel file csv</label>
            <input class="form-control form-control-lg" id="formFileLg" type="file" name="file">
            <button type="submit" class="btn btn-primary mb-3 mt-1">Submit</button>
        </div>
        </form>
    @endif

    <script>
        // JavaScript
        document.addEventListener('DOMContentLoaded', function() {
            const checkAll = document.getElementById('all');
            const checkboxes = document.querySelectorAll('.form-check-input');

            // Add event listener to "check all" checkbox
            checkAll.addEventListener('change', function() {
                checkboxes.forEach(function(checkbox) {
                    checkbox.checked = checkAll.checked;
                });
            });

            // Add event listener to individual checkboxes
            checkboxes.forEach(function(checkbox) {
                checkbox.addEventListener('change', function() {
                    // Check if all checkboxes are checked
                    const allChecked = [...checkboxes].every(function(checkbox) {
                        return checkbox.checked;
                    });
                    // If all checkboxes are checked, check "check all" checkbox
                    if (allChecked) {
                        checkAll.checked = true;
                    } else {
                        checkAll.checked = false;
                    }
                    // If all checkboxes are checked individually, check "check all" checkbox
                    if (allChecked && checkboxes.length === [...document.querySelectorAll('.check:checked')].length) {
                        checkAll.checked = true;
                    }
                });
            });
        });
    </script>
@endsection('content')
