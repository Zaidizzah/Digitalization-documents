<!-- Footer Section & Essential javascripts for application to work-->
<script type="text/javascript" src="{{ asset('assets/vali-admin-master/js/jquery-3.7.0.min.js') }}"></script>
<script type="text/javascript" src="{{ asset('assets/vali-admin-master/js/bootstrap.min.js ') }}"></script>
<script type="text/javascript" src="{{ asset('assets/vali-admin-master/js/main.js') }}"></script>
<script type="text/javascript" src="{{ asset('resources/plugins/imgpreview/js/imgpreview.js') }}"></script>
<!-- The Application javascripts -->
<script type="text/javascript" src="{{ asset('resources/apps/scripts.js') }}"></script>
<!-- The Application javascripts for specific page -->
@generate_tags('script', $javascript)
<!-- The javascript plugin-->