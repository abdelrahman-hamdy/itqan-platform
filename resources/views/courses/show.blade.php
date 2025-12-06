@auth
  @if(auth()->user()->isAdmin() || auth()->user()->isSuperAdmin())
    {{-- Show public layout for admin/superadmin --}}
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
