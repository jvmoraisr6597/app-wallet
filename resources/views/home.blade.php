@extends('layouts.app')

@section('content')
<div class="container">
    <user-assets :user-id="{{ auth()->user()->id }}"></user-assets>
</div>
@endsection
