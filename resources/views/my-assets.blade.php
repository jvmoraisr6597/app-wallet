@extends('layouts.app')

@section('content')
<div class="container">
    <my-assets :user-id="{{ auth()->user()->id }}"></my-assets>
</div>
@endsection