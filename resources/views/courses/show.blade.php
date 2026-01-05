@auth
  @if(auth()->user()->isAdmin() || auth()->user()->isSuperAdmin() || auth()->user()->isSupervisor())
    {{-- Show public layout for admin/superadmin/supervisor --}}
    <x-layouts.public-layout
      :title="$course->title . ' - ' . $academy->name"
      :description="$course->description"
      :academy="$academy">
      @include('courses.partials.show-content')
    </x-layouts.public-layout>
  @else
    {{-- Show student layout for regular users --}}
    <x-layouts.student
      :title="$course->title . ' - ' . $academy->name"
      :description="$course->description">
      @include('courses.partials.show-content')
    </x-layouts.student>
  @endif
@else
  {{-- Show public layout for guests --}}
  <x-layouts.public-layout
    :title="$course->title . ' - ' . $academy->name"
    :description="$course->description"
    :academy="$academy">
    @include('courses.partials.show-content')
  </x-layouts.public-layout>
@endauth
