@extends('base')
@section('content')
<div class="pagetitle">
    <h1>Result Page</h1>
    <nav>
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="index.html">Home</a></li>
            <li class="breadcrumb-item">Pages</li>
            <li class="breadcrumb-item active">Result</li>
        </ol>
    </nav>
</div><!-- End Page Title -->

<section class="section">
    <div class="row">
        <div class="col-lg-6">

            <div class="card">
                <div class="card-body">
                    <h5 class="card-title">Update Result</h5>
                    <form method="post" action="{{ route('result.update', $result->id) }}">
                        @csrf
                        <div class="row mb-1">
                            <label for="inputEmail3" class="col-sm-2 col-form-label">Play Date</label>
                            <div class="col-sm-10">
                                <input type="date" class="form-control" name="play_date" value="{{ $result->play_date->format('Y-m-d') }}">
                            </div>
                            @error('play_date')
                            <small class="text-danger">{{ $errors->first('play_date') }}</small>
                            @enderror
                        </div>
                        <div class="row mb-1">
                            <label for="inputEmail3" class="col-sm-2 col-form-label">Play</label>
                            <div class="col-sm-10">
                                <select class="form-control" name="play_id">
                                    <option value="">Select Play</option>
                                    @forelse($plays as $key => $play)
                                    <option value="{{ $play->id }}" {{ ($result->play_id == $play->id) ? 'selected' : '' }}>{{ $play->name }}</option>
                                    @empty
                                    @endforelse
                                </select>
                            </div>
                            @error('play_id')
                            <small class="text-danger">{{ $errors->first('play_id') }}</small>
                            @enderror
                        </div>
                        <div class="row mb-1">
                            <label for="inputPassword3" class="col-sm-2 col-form-label">P1</label>
                            <div class="col-sm-10">
                                <input type="text" class="form-control num" value="{{ $result->p1 }}" name="p1" max="999" placeholder="xxx">
                            </div>
                        </div>
                        <div class="row mb-1">
                            <label for="inputPassword3" class="col-sm-2 col-form-label">P2</label>
                            <div class="col-sm-10">
                                <input type="text" class="form-control num" value="{{ $result->p2 }}" name="p2" max="999" placeholder="xxx">
                            </div>
                        </div>
                        <div class="row mb-1">
                            <label for="inputPassword3" class="col-sm-2 col-form-label">P3</label>
                            <div class="col-sm-10">
                                <input type="text" class="form-control num" value="{{ $result->p3 }}" name="p3" max="999" placeholder="xxx">
                            </div>
                        </div>
                        <div class="row mb-1">
                            <label for="inputPassword3" class="col-sm-2 col-form-label">P4</label>
                            <div class="col-sm-10">
                                <input type="text" class="form-control num" value="{{ $result->p4 }}" name="p4" max="999" placeholder="xxx">
                            </div>
                        </div>
                        <div class="row mb-1">
                            <label for="inputPassword3" class="col-sm-2 col-form-label">P5</label>
                            <div class="col-sm-10">
                                <input type="text" class="form-control num" value="{{ $result->p5 }}" name="p5" max="999" placeholder="xxx">
                            </div>
                        </div>
                        <div class="row mb-1">
                            <label for="inputPassword3" class="col-sm-2 col-form-label">P6</label>
                            <div class="col-sm-10">
                                <input type="text" class="form-control num" value="{{ $result->p6 }}" name="p6" max="999" placeholder="xxx">
                            </div>
                        </div>
                        <div class="row mb-1">
                            <label for="inputPassword3" class="col-sm-2 col-form-label">P7</label>
                            <div class="col-sm-10">
                                <input type="text" class="form-control num" value="{{ $result->p7 }}" name="p7" max="999" placeholder="xxx">
                            </div>
                        </div>
                        <div class="row mb-1">
                            <label for="inputPassword3" class="col-sm-2 col-form-label">P8</label>
                            <div class="col-sm-10">
                                <input type="text" class="form-control num" value="{{ $result->p8 }}" name="p8" max="999" placeholder="xxx">
                            </div>
                        </div>
                        <div class="row mb-1">
                            <label for="inputPassword3" class="col-sm-2 col-form-label">P9</label>
                            <div class="col-sm-10">
                                <input type="text" class="form-control num" value="{{ $result->p9 }}" name="p9" max="999" placeholder="xxx">
                            </div>
                        </div>
                        <div class="row mb-1">
                            <label for="inputPassword3" class="col-sm-2 col-form-label">P10</label>
                            <div class="col-sm-10">
                                <input type="text" class="form-control num" value="{{ $result->p10 }}" name="p10" max="999" placeholder="xxx">
                            </div>
                        </div>
                        <div class="row mb-1">
                            <label for="inputPassword3" class="col-sm-2 col-form-label">P11</label>
                            <div class="col-sm-10">
                                <input type="text" class="form-control num" value="{{ $result->p11 }}" name="p11" max="999" placeholder="xxx">
                            </div>
                        </div>
                        <div class="row mb-1">
                            <label for="inputPassword3" class="col-sm-2 col-form-label">P12</label>
                            <div class="col-sm-10">
                                <input type="text" class="form-control num" value="{{ $result->p12 }}" name="p12" max="999" placeholder="xxx">
                            </div>
                        </div>
                        <div class="row mb-1">
                            <label for="inputPassword3" class="col-sm-2 col-form-label">P13</label>
                            <div class="col-sm-10">
                                <input type="text" class="form-control num" value="{{ $result->p13 }}" name="p13" max="999" placeholder="xxx">
                            </div>
                        </div>
                        <div class="row mb-1">
                            <label for="inputPassword3" class="col-sm-2 col-form-label">P14</label>
                            <div class="col-sm-10">
                                <input type="text" class="form-control num" value="{{ $result->p14 }}" name="p14" max="999" placeholder="xxx">
                            </div>
                        </div>
                        <div class="row mb-1">
                            <label for="inputPassword3" class="col-sm-2 col-form-label">P15</label>
                            <div class="col-sm-10">
                                <input type="text" class="form-control num" value="{{ $result->p15 }}" name="p15" max="999" placeholder="xxx">
                            </div>
                        </div>
                        <div class="row mb-1">
                            <label for="inputPassword3" class="col-sm-2 col-form-label">P16</label>
                            <div class="col-sm-10">
                                <input type="text" class="form-control num" value="{{ $result->p16 }}" name="p16" max="999" placeholder="xxx">
                            </div>
                        </div>
                        <div class="row mb-1">
                            <label for="inputPassword3" class="col-sm-2 col-form-label">P17</label>
                            <div class="col-sm-10">
                                <input type="text" class="form-control num" value="{{ $result->p17 }}" name="p17" max="999" placeholder="xxx">
                            </div>
                        </div>
                        <div class="row mb-1">
                            <label for="inputPassword3" class="col-sm-2 col-form-label">P18</label>
                            <div class="col-sm-10">
                                <input type="text" class="form-control num" value="{{ $result->p18 }}" name="p18" max="999" placeholder="xxx">
                            </div>
                        </div>
                        <div class="row mb-1">
                            <label for="inputPassword3" class="col-sm-2 col-form-label">P19</label>
                            <div class="col-sm-10">
                                <input type="text" class="form-control num" value="{{ $result->p19 }}" name="p19" max="999" placeholder="xxx">
                            </div>
                        </div>
                        <div class="row mb-1">
                            <label for="inputPassword3" class="col-sm-2 col-form-label">P20</label>
                            <div class="col-sm-10">
                                <input type="text" class="form-control num" value="{{ $result->p20 }}" name="p20" max="999" placeholder="xxx">
                            </div>
                        </div>
                        <div class="row mb-1">
                            <label for="inputPassword3" class="col-sm-2 col-form-label">P21</label>
                            <div class="col-sm-10">
                                <input type="text" class="form-control num" value="{{ $result->p21 }}" name="p21" max="999" placeholder="xxx">
                            </div>
                        </div>
                        <div class="row mb-1">
                            <label for="inputPassword3" class="col-sm-2 col-form-label">P22</label>
                            <div class="col-sm-10">
                                <input type="text" class="form-control num" value="{{ $result->p22 }}" name="p22" max="999" placeholder="xxx">
                            </div>
                        </div>
                        <div class="row mb-1">
                            <label for="inputPassword3" class="col-sm-2 col-form-label">P23</label>
                            <div class="col-sm-10">
                                <input type="text" class="form-control num" value="{{ $result->p23 }}" name="p23" max="999" placeholder="xxx">
                            </div>
                        </div>
                        <div class="row mb-1">
                            <label for="inputPassword3" class="col-sm-2 col-form-label">P24</label>
                            <div class="col-sm-10">
                                <input type="text" class="form-control num" value="{{ $result->p24 }}" name="p24" max="999" placeholder="xxx">
                            </div>
                        </div>
                        <div class="row mb-1">
                            <label for="inputPassword3" class="col-sm-2 col-form-label">P25</label>
                            <div class="col-sm-10">
                                <input type="text" class="form-control num" value="{{ $result->p25 }}" name="p25" max="999" placeholder="xxx">
                            </div>
                        </div>
                        <div class="row mb-1">
                            <label for="inputPassword3" class="col-sm-2 col-form-label">P26</label>
                            <div class="col-sm-10">
                                <input type="text" class="form-control num" value="{{ $result->p26 }}" name="p26" max="999" placeholder="xxx">
                            </div>
                        </div>
                        <div class="row mb-1">
                            <label for="inputPassword3" class="col-sm-2 col-form-label">P27</label>
                            <div class="col-sm-10">
                                <input type="text" class="form-control num" value="{{ $result->p27 }}" name="p27" max="999" placeholder="xxx">
                            </div>
                        </div>
                        <div class="row mb-1">
                            <label for="inputPassword3" class="col-sm-2 col-form-label">P28</label>
                            <div class="col-sm-10">
                                <input type="text" class="form-control num" value="{{ $result->p28 }}" name="p28" max="999" placeholder="xxx">
                            </div>
                        </div>
                        <div class="row mb-1">
                            <label for="inputPassword3" class="col-sm-2 col-form-label">P29</label>
                            <div class="col-sm-10">
                                <input type="text" class="form-control num" value="{{ $result->p29 }}" name="p29" max="999" placeholder="xxx">
                            </div>
                        </div>
                        <div class="row mb-1">
                            <label for="inputPassword3" class="col-sm-2 col-form-label">P30</label>
                            <div class="col-sm-10">
                                <input type="text" class="form-control num" value="{{ $result->p30 }}" name="p30" max="999" placeholder="xxx">
                            </div>
                        </div>
                        <div class="row mb-1">
                            <label for="inputPassword3" class="col-sm-2 col-form-label">P31</label>
                            <div class="col-sm-10">
                                <input type="text" class="form-control num" value="{{ $result->p31 }}" name="p31" max="999" placeholder="xxx">
                            </div>
                        </div>
                        <div class="row mb-1">
                            <label for="inputPassword3" class="col-sm-2 col-form-label">P32</label>
                            <div class="col-sm-10">
                                <input type="text" class="form-control num" value="{{ $result->p32 }}" name="p32" max="999" placeholder="xxx">
                            </div>
                        </div>
                        <div class="row mb-1">
                            <label for="inputPassword3" class="col-sm-2 col-form-label">P33</label>
                            <div class="col-sm-10">
                                <input type="text" class="form-control num" value="{{ $result->p33 }}" name="p33" max="999" placeholder="xxx">
                            </div>
                        </div>
                        <div class="row mb-1">
                            <label for="inputPassword3" class="col-sm-2 col-form-label">P34</label>
                            <div class="col-sm-10">
                                <input type="text" class="form-control num" value="{{ $result->p34 }}" name="p34" max="999" placeholder="xxx">
                            </div>
                        </div>
                        <div class="row mb-1">
                            <label for="inputPassword3" class="col-sm-2 col-form-label">P35</label>
                            <div class="col-sm-10">
                                <input type="text" class="form-control num" value="{{ $result->p35 }}" name="p35" max="999" placeholder="xxx">
                            </div>
                        </div>
                        <div class="text-center mt-3">
                            <button type="submit" class="btn btn-primary btn-submit">Update</button>
                            <button type="reset" class="btn btn-secondary">Cancel</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</section>
@endsection('content')