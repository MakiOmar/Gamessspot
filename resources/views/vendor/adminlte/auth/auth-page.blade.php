@extends('adminlte::master')

@section('adminlte_css')
    @stack('css')
    @yield('css')
@stop

@section('classes_body'){{ ($auth_type ?? 'login') . '-page' }}@stop

@section('body')
    @yield('auth_body')
@stop

@section('adminlte_js')
    @stack('js')
    @yield('js')
@stop
