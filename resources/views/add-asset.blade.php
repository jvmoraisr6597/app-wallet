@extends('layouts.app')

@section('content')
<div class="container">
    <add-asset-form :user-id="{{ auth()->user()->id }}"></add-asset-form>
</div>
@endsection
