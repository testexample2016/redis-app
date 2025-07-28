@extends('products.layout')

 

@section('content')

    <div class="row">

        <div class="col-lg-12 margin-tb">

            <div class="pull-left">

                <h2>Laravel 8 CRUD Example with Redis </h2>

            </div>


            <div class="pull-right">

                <a class="btn btn-success" href="{{ route('products.create') }}"> Create New Product</a>

            </div>

        </div>

    </div>

   

    @if ($message = Session::get('success'))

        <div class="alert alert-success">

            <p>{{ $message }}</p>

        </div>

    @endif
    
<div class="row mb-4 align-items-center">
    <div class="col-auto">
        <a class="btn btn-primary" href="{{ route('products.index') }}"> Home</a>
    </div>
    <div class="col-auto">
        <form action="{{ route('products.search') }}" method="GET" class="d-flex gap-2">
            <input type="text"
                   name="q"
                   value="{{ request('q') }}"
                   placeholder="Search products..."
                   class="border rounded p-2 flex-1">
            <button type="submit" class="btn btn-primary">
                Search
            </button>
        </form>
    </div>
</div>
    <table class="table table-bordered">

        <tr>

            <th>No</th>

            <th>Name</th>

            <th>Details</th>

            <th width="280px">Action</th>

        </tr>

        @foreach ($products as $product)

        <tr>

            <td>{{ ++$i }}</td>

            <td>{{ $product->name }}</td>

            <td>{{ $product->detail }}</td>

            <td>

                <form action="{{ route('products.destroy',$product->id) }}" method="POST">

   

                    <a class="btn btn-info" href="{{ route('products.show',$product->id) }}">Show</a>

    

                    <a class="btn btn-primary" href="{{ route('products.edit',$product->id) }}">Edit</a>

   

                    @csrf

                    @method('DELETE')

      

                    <button type="submit" class="btn btn-danger">Delete</button>

                </form>

            </td>

        </tr>

        @endforeach

    </table>

  

    {!! $products->links() !!}

      

@endsection